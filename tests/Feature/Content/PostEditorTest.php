<?php

use App\Modules\Content\Filament\Resources\Posts\Pages\CreatePost;
use App\Modules\Content\Filament\Resources\Posts\Pages\EditPost;
use App\Modules\Content\Filament\Resources\Posts\Pages\ListPosts;
use App\Modules\Content\Filament\Resources\Posts\PostResource;
use App\Modules\Content\Models\Post;
use App\Modules\Content\Models\Redirect;
use App\Modules\Logs\Models\AuditLog;
use App\Modules\Settings\SettingsRepository;
use Livewire\Livewire;

function goodArticle(): string
{
    $sentences = ['Caching saves time.', 'A cache keeps a finished copy of each page so the server can skip the expensive work for the next visitor.', 'You will notice it.', 'Images matter too, because they are usually the heaviest part of the page and slow down every first visit.'];
    $html = '<p>Do you want a faster site? Cache your pages, compress images and ship less JavaScript: those three steps fix most slow websites today.</p>';
    foreach (['Why is my site slow?', 'How does caching work?', 'Which images should you compress?', 'What about JavaScript?'] as $h) {
        $html .= "<h2>$h</h2>";
        for ($i = 0; $i < 8; $i++) {
            $html .= '<p>'.implode(' ', array_map(fn ($j) => $sentences[($i + $j) % 4], range(0, 3))).' Site speed helps.</p>';
        }
    }

    return $html.'<ul><li>Cache</li><li>Compress</li></ul><p>See the <a href="/blog/other">guide</a>, <strong>measure</strong> first.</p>';
}

function articleData(array $overrides = []): array
{
    return $overrides + [
        'title' => 'Site speed: the practical guide',
        'slug' => 'site-speed',
        'content' => goodArticle(),
        'keywords' => ['site speed'],
        'meta_description' => str_repeat('Fast pages rank better and convert better. ', 3).'Here is how.',
        'status' => 'draft',
        'locale' => 'en',
    ];
}

it('renders the editor with the scoring panel and media modal', function () {
    $this->actingAs(editor());

    $this->get(PostResource::getUrl('create'))->assertOk()
        ->assertSee('lwkEditorPanel', false)
        ->assertSee('lwkMediaModal', false)
        ->assertSee('lwkSnippet', false);
});

it('creates a post, stores server-side scores and sanitizes the html', function () {
    $this->actingAs($user = editor());

    Livewire::test(CreatePost::class)
        ->fillForm(articleData(['content' => goodArticle().'<script>alert(1)</script><p onclick="x()">Hi</p>']))
        ->call('create')
        ->assertHasNoFormErrors();

    $post = Post::sole();
    expect($post->content)->not->toContain('<script')->not->toContain('onclick')
        ->and($post->seo_score)->toBeGreaterThanOrEqual(60)
        ->and($post->human_score)->toBeGreaterThanOrEqual(45)
        ->and($post->geo_score)->not->toBeNull()
        ->and($post->word_count)->toBeGreaterThan(300)
        ->and($post->author_id)->toBe($user->id)
        ->and($post->revisions()->count())->toBe(1)
        ->and(AuditLog::where('auditable_type', 'post')->where('event', 'created')->exists())->toBeTrue();
});

it('blocks publishing below the gate and allows an override with a logged reason', function () {
    $this->actingAs(editor());
    $thin = articleData(['content' => '<p>Too short.</p>', 'status' => 'published']);

    Livewire::test(CreatePost::class)->fillForm($thin)->call('create')->assertNotified();
    expect(Post::count())->toBe(0);

    Livewire::test(CreatePost::class)->fillForm($thin + ['gate_override_reason' => 'Breaking news, expand later'])->call('create')->assertHasNoFormErrors();

    $post = Post::sole();
    expect($post->isPublished())->toBeTrue();
    $log = AuditLog::where('event', 'gate_override')->sole();
    expect($log->reason)->toBe('Breaking news, expand later')->and($log->new_values['failures'])->not->toBeEmpty();
});

it('lets good content through the gate and can disable the gate', function () {
    $this->actingAs(editor());

    Livewire::test(CreatePost::class)->fillForm(articleData(['status' => 'published']))->call('create')->assertHasNoFormErrors();
    expect(Post::sole()->isPublished())->toBeTrue();

    app(SettingsRepository::class)->set('features.publish_gate', false);
    Livewire::test(CreatePost::class)->fillForm(articleData(['slug' => 'thin', 'content' => '<p>x</p>', 'status' => 'published']))->call('create')->assertHasNoFormErrors();
    expect(Post::where('slug', 'thin')->sole()->isPublished())->toBeTrue();
});

it('turns an author\'s publish into a review request and scopes authors to their own posts', function () {
    $author = author();
    $other = Post::factory()->create(['author_id' => editor()->id, 'title' => 'Someone else']);
    $this->actingAs($author);

    Livewire::test(CreatePost::class)->fillForm(articleData(['status' => 'pending']))->call('create')->assertHasNoFormErrors();

    $mine = Post::where('author_id', $author->id)->sole();
    expect($mine->status)->toBe('pending');

    // Even a forged "published" state is turned into a review request.
    $page = new CreatePost;
    $data = (fn () => $this->prepareContentData(['status' => 'published'], null))->call($page);
    expect($data['status'])->toBe('pending');

    Livewire::test(ListPosts::class)->assertCanSeeTableRecords([$mine])->assertCanNotSeeTableRecords([$other]);
    $this->get(PostResource::getUrl('edit', ['record' => $other]))->assertNotFound();
});

it('schedules a post with a future date', function () {
    $post = Post::factory()->create(['status' => 'published', 'published_at' => now()->addDay()]);

    expect($post->isScheduled())->toBeTrue()->and($post->effectiveStatus())->toBe('scheduled')
        ->and(Post::published()->count())->toBe(0)->and($post->announced_at)->toBeNull();

    $this->travel(2)->days();
    $this->artisan('larawebkit:content:announce')->assertSuccessful();
    expect($post->fresh()->announced_at)->not->toBeNull();
});

it('creates a 301 redirect when a published slug changes and collapses chains', function () {
    $post = Post::factory()->published()->create(['slug' => 'first']);
    $post->update(['slug' => 'second']);
    $post->update(['slug' => 'third']);

    expect(Redirect::where('from_path', '/blog/first')->value('to_url'))->toBe('/blog/third')
        ->and(Redirect::where('from_path', '/blog/second')->value('to_url'))->toBe('/blog/third');

    $this->get('/blog/first')->assertRedirect('/blog/third')->assertStatus(301);
});

it('does not redirect when a draft slug changes', function () {
    Post::factory()->create(['slug' => 'draft-a'])->update(['slug' => 'draft-b']);

    expect(Redirect::count())->toBe(0);
});

it('keeps slugs unique per locale', function () {
    $a = Post::factory()->create(['title' => 'Same', 'locale' => 'en']);
    $b = Post::factory()->create(['title' => 'Same', 'locale' => 'en']);
    $c = Post::factory()->create(['title' => 'Same', 'locale' => 'tr']);

    expect([$a->slug, $b->slug, $c->slug])->toBe(['same', 'same-2', 'same']);
});

it('autosaves a draft revision and offers to restore it', function () {
    $this->actingAs(editor());
    $post = Post::factory()->create(['author_id' => auth()->id(), 'title' => 'Saved title']);

    $this->travel(1)->minute();
    Livewire::test(EditPost::class, ['record' => $post->getRouteKey()])
        ->fillForm(['title' => 'Typed but not saved', 'content' => '<p>Typed body</p>'])
        ->call('autosave');

    expect($post->fresh()->title)->toBe('Saved title')
        ->and($post->latestAutosave()->data['title'])->toBe('Typed but not saved')
        ->and($post->latestAutosave()->data['content'])->toContain('<p>Typed body</p>');

    Livewire::test(EditPost::class, ['record' => $post->getRouteKey()])
        ->assertActionVisible('restoreAutosave')
        ->callAction('restoreAutosave')
        ->assertSchemaStateSet(['title' => 'Typed but not saved']);
});

it('links translations and outputs hreflang', function () {
    app(SettingsRepository::class)->set('general.active_locales', ['en', 'tr']);
    $en = Post::factory()->published()->create(['title' => 'Hello', 'slug' => 'hello', 'locale' => 'en']);
    $tr = Post::factory()->published()->create(['title' => 'Merhaba', 'slug' => 'merhaba', 'locale' => 'tr', 'translation_group' => $en->translation_group]);

    $this->get('/blog/hello')->assertOk()
        ->assertSee('hreflang="tr" href="'.url('/tr/blog/merhaba').'"', false)
        ->assertSee('hreflang="x-default"', false);
    $this->get('/tr/blog/merhaba')->assertOk()->assertSee('<html lang="tr"', false);
    $this->get('/en/blog/hello')->assertNotFound();
});
