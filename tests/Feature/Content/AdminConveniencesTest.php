<?php

use App\Models\User;
use App\Modules\Content\Filament\ContentActions;
use App\Modules\Content\Filament\Resources\Pages\Pages\ListPages;
use App\Modules\Content\Filament\Resources\Posts\Pages\EditPost;
use App\Modules\Content\Filament\Resources\Posts\Pages\ListPosts;
use App\Modules\Content\Models\Category;
use App\Modules\Content\Models\Page;
use App\Modules\Content\Models\Post;
use App\Modules\Settings\Filament\Pages\ManageSettings;
use App\Modules\Settings\Mail\TestMail;
use App\Modules\Settings\SettingsRepository;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    app(SettingsRepository::class)->set('features.page_cache', false);
});

it('duplicates a post as a draft with its categories and tags', function () {
    $this->actingAs(superAdmin());
    $news = Category::create(['name' => 'News', 'slug' => 'news']);
    $post = Post::factory()->published()->create(['title' => 'Launch day', 'slug' => 'launch-day']);
    $post->categories()->attach($news);

    Livewire::test(EditPost::class, ['record' => $post->id])->callAction('duplicate')->assertRedirect();

    $copy = Post::where('id', '!=', $post->id)->sole();
    expect($copy->title)->toBe('Launch day (copy)')
        ->and($copy->slug)->not->toBe('launch-day')
        ->and($copy->status)->toBe('draft')
        ->and($copy->published_at)->toBeNull()
        ->and($copy->translation_group)->not->toBe($post->translation_group)
        ->and($copy->categories->pluck('slug')->all())->toBe(['news'])
        ->and($post->fresh()->title)->toBe('Launch day');
});

it('duplicates a page from the table', function () {
    $this->actingAs(superAdmin());
    $page = Page::factory()->create(['title' => 'Services', 'slug' => 'services', 'blocks' => [['type' => 'rich_text', 'data' => ['body' => '<p>Hi</p>']]]]);

    Livewire::test(ListPages::class)->callTableAction('duplicate', $page);

    $copy = Page::where('id', '!=', $page->id)->sole();
    expect($copy->path)->not->toBe($page->path)->and($copy->blocks)->toBe($page->blocks)->and($copy->status)->toBe('draft');
});

it('shares a draft through a signed, expiring preview link', function () {
    $post = Post::factory()->create(['title' => 'Secret draft', 'slug' => 'secret-draft', 'status' => 'draft']);
    $this->get('/blog/secret-draft')->assertNotFound();

    $url = ContentActions::previewUrl('post', $post);
    $this->get($url)->assertOk()->assertSee('Secret draft')->assertSee(__('larawebkit-front.preview'))
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')->assertHeader('Cache-Control', 'no-store, private');
    $this->get(str_replace('/post/', '/page/', $url))->assertForbidden();
    $this->get('/_preview/shared/post/'.$post->id)->assertForbidden();

    $this->travel(config('larawebkit.preview_link_hours') + 1)->hours();
    $this->get($url)->assertForbidden();
});

it('publishes, unpublishes and recategorises posts in bulk, respecting the publish gate', function () {
    $this->actingAs(superAdmin());
    $drafts = Post::factory()->count(2)->create(['status' => 'draft']);
    $news = Category::create(['name' => 'News', 'slug' => 'news']);

    Livewire::test(ListPosts::class)->callTableBulkAction('publish', $drafts);
    expect(Post::published()->count())->toBe(2);

    Livewire::test(ListPosts::class)->callTableBulkAction('moveCategory', $drafts, ['category' => $news->id, 'replace' => true]);
    expect($news->posts()->count())->toBe(2);

    Livewire::test(ListPosts::class)->callTableBulkAction('unpublish', $drafts);
    expect(Post::where('status', 'draft')->count())->toBe(2);

    // A publisher who may not override the gate cannot bulk-publish thin drafts.
    app(SettingsRepository::class)->set('features.publish_gate', true);
    $role = Role::create(['name' => 'publisher', 'guard_name' => 'web']);
    $role->givePermissionTo(['view dashboard', 'manage posts', 'publish content']);
    $publisher = User::factory()->create();
    $publisher->assignRole($role);
    $thin = Post::factory()->create(['status' => 'draft', 'content' => '<p>Too short.</p>']);
    $this->actingAs($publisher);
    Livewire::test(ListPosts::class)->callTableBulkAction('publish', [$thin])->assertNotified();
    expect($thin->fresh()->status)->toBe('draft');
});

it('lists scheduled posts in their own tab', function () {
    $this->actingAs(superAdmin());
    $scheduled = Post::factory()->create(['title' => 'Coming soon', 'status' => 'published', 'published_at' => now()->addDays(2)]);
    $live = Post::factory()->published()->create(['title' => 'Already out']);

    Livewire::test(ListPosts::class)->set('activeTab', 'scheduled')
        ->assertCanSeeTableRecords([$scheduled])->assertCanNotSeeTableRecords([$live]);
    Livewire::test(ListPosts::class)->set('activeTab', 'published')
        ->assertCanSeeTableRecords([$live])->assertCanNotSeeTableRecords([$scheduled]);
});

it('sends a test e-mail from the settings page', function () {
    Mail::fake();
    $this->actingAs(superAdmin());

    Livewire::test(ManageSettings::class)->callAction('testMail', ['email' => 'me@example.com'])->assertNotified();

    Mail::assertSent(TestMail::class, fn ($m) => $m->hasTo('me@example.com'));
    expect((new TestMail)->render())->toContain(__('larawebkit-settings.mail_test.body'));
});
