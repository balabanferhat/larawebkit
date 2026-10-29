<?php

use App\Modules\Content\Filament\RelationManagers\RevisionsRelationManager;
use App\Modules\Content\Filament\Resources\Pages\Pages\CreatePage;
use App\Modules\Content\Models\Menu;
use App\Modules\Content\Models\Page;
use App\Modules\Content\Models\Post;
use App\Modules\Content\Models\Redirect;
use App\Modules\Content\Support\HtmlSanitizer;
use App\Modules\Content\Support\TextDiff;
use App\Modules\Media\ImagePipeline;
use Database\Seeders\LarawebkitSeeder;
use Livewire\Livewire;

it('builds hierarchical paths and moves children with their parent', function () {
    $about = Page::factory()->published()->create(['title' => 'About', 'slug' => 'about']);
    $team = Page::factory()->published()->create(['title' => 'Team', 'slug' => 'team', 'parent_id' => $about->id]);

    expect($team->path)->toBe('about/team')->and($team->publicPath())->toBe('/about/team');
    $this->get('/about/team')->assertOk()->assertSee('Team');

    $about->update(['slug' => 'company']);
    expect($team->fresh()->path)->toBe('company/team')
        ->and(Redirect::where('from_path', '/about/team')->value('to_url'))->toBe('/company/team');
});

it('allows the same slug under different parents', function () {
    $a = Page::factory()->create(['slug' => 'a']);
    $b = Page::factory()->create(['slug' => 'b']);
    $x1 = Page::factory()->create(['slug' => 'faq', 'parent_id' => $a->id]);
    $x2 = Page::factory()->create(['slug' => 'faq', 'parent_id' => $b->id]);

    expect([$x1->slug, $x2->slug])->toBe(['faq', 'faq']);
});

it('creates a page with a template from the admin', function () {
    $this->actingAs(editor());

    Livewire::test(CreatePage::class)
        ->fillForm(['title' => 'Contact us', 'slug' => 'contact-us', 'template' => 'contact', 'status' => 'draft', 'locale' => 'en', 'content' => '<p>Write to us.</p>'])
        ->call('create')->assertHasNoFormErrors();

    expect(Page::where('slug', 'contact-us')->value('template'))->toBe('contact');
});

it('stores a revision on every save, diffs and restores it', function () {
    $this->actingAs(editor());
    $post = Post::factory()->create(['title' => 'Version one', 'content' => '<p>Old words here.</p>']);
    $post->update(['title' => 'Version two', 'content' => '<p>New words here.</p>']);

    expect($post->revisions()->count())->toBe(2);
    $first = $post->revisions()->reorder('id')->first();

    $diff = RevisionsRelationManager::diff($first, $post->fresh());
    expect($diff)->toContain('<del class="lwk-diff-del">Old</del>')->toContain('<ins class="lwk-diff-add">New</ins>');

    $post->restoreRevision($first);
    expect($post->fresh()->title)->toBe('Version one')->and($post->revisions()->count())->toBe(3);
});

it('computes word diffs', function () {
    expect(TextDiff::words('a b c', 'a x c'))->toBe([
        ['type' => 'same', 'text' => 'a '], ['type' => 'del', 'text' => 'b'], ['type' => 'add', 'text' => 'x'], ['type' => 'same', 'text' => ' c'],
    ]);
});

it('sanitizes dangerous html but keeps editor formatting', function () {
    $clean = app(HtmlSanitizer::class)->sanitize('<h2 style="text-align: center; position: fixed">T</h2><p>a <a href="javascript:alert(1)">x</a> <a href="/ok">ok</a></p><iframe src="x"></iframe><img src="/a.webp" alt="A" data-id="5" onerror="x()">');

    expect($clean)->toContain('<h2 style="text-align: center">T</h2>')
        ->toContain('<a href="/ok">ok</a>')
        ->toContain('data-id="5"')
        ->not->toContain('javascript:')->not->toContain('iframe')->not->toContain('onerror')->not->toContain('position');
});

it('tracks media usage for featured and inline images', function () {
    $pipeline = app(ImagePipeline::class);
    $featured = $pipeline->store(fakeJpeg(800, 500), 'f.jpg');
    $inline = $pipeline->store(fakeJpeg(800, 500), 'i.jpg');

    $post = Post::factory()->create(['featured_media_id' => $featured->id, 'content' => '<p><img src="x" alt="i" data-id="'.$inline->id.'"></p>']);
    expect($featured->usageCount())->toBe(1)->and($inline->usageCount())->toBe(1);

    $post->update(['content' => '<p>no images</p>']);
    expect($inline->fresh()->usageCount())->toBe(0)->and($featured->usageCount())->toBe(1);
});

it('renders cached header and footer menus with page links', function () {
    $page = Page::factory()->published()->create(['title' => 'Pricing', 'slug' => 'pricing']);
    $menu = Menu::create(['name' => 'Header', 'location' => 'header']);
    $menu->items()->create(['label' => 'Pricing', 'linkable_type' => 'page', 'linkable_id' => $page->id, 'sort_order' => 1]);
    $menu->items()->create(['label' => 'Docs', 'url' => 'https://docs.example.com', 'new_tab' => true, 'sort_order' => 2]);

    expect(Menu::for('header')->pluck('url')->all())->toBe(['/pricing', 'https://docs.example.com']);
    $this->get('/blog')->assertSee('href="/pricing"', false)->assertSee('target="_blank"', false);
});

it('seeds menus and privacy/cookie policy stubs idempotently', function () {
    $this->seed(LarawebkitSeeder::class);
    $this->seed(LarawebkitSeeder::class);

    expect(Page::where('slug', 'privacy-policy')->count())->toBe(1)
        ->and(Page::where('slug', 'cookie-policy')->value('noindex'))->toBeTrue()
        ->and(Menu::pluck('location')->sort()->values()->all())->toBe(['footer', 'header']);
    $this->get('/privacy-policy')->assertOk()->assertSee('Template – review before publishing');
});
