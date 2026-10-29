<?php

use App\Modules\Content\Models\Menu;
use App\Modules\Content\Models\Page;
use App\Modules\Content\Models\Post;
use App\Modules\Media\Models\Media;
use App\Modules\Settings\SettingsRepository;
use App\Modules\Themes\Demo\DemoBuilder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('local');
    app(SettingsRepository::class)->set('features.page_cache', false);
});

afterEach(fn () => File::delete(DemoBuilder::manifestPath()));

it('builds a working demo site for every bundled theme', function (string $theme, string $locale) {
    app(SettingsRepository::class)->set('general.default_locale', $locale);
    superAdmin();

    $this->artisan('larawebkit:demo', ['theme' => $theme, '--image-scale' => 0.05])->assertSuccessful();

    expect(setting('general.theme'))->toBe($theme)
        ->and(Post::published()->count())->toBeGreaterThanOrEqual(3)
        ->and(Menu::where('location', 'header')->first()->items()->count())->toBeGreaterThan(2)
        ->and(Media::where('source', 'demo')->count())->toBeGreaterThan(0)
        ->and(json_encode(Page::pluck('title')))->not->toContain('demo.');

    $html = $this->get('/')->assertOk()->assertSee('data-theme-name="'.$theme.'"', false)->getContent();
    expect(substr_count($html, '<h1'))->toBe(1);
    $this->get(Post::published()->first()->publicPath())->assertOk();
    foreach (Page::published()->where('template', '!=', 'legal')->get() as $page) {
        $this->get($page->publicPath())->assertOk();
    }
    $this->get('/blog')->assertOk();
    $this->get('/search?q=a')->assertOk();
})->with(['business', 'personal', 'magazine', 'docs'])->with(['en', 'tr']);

it('replaces the previous demo when run again and can remove it', function () {
    superAdmin();
    $this->artisan('larawebkit:demo', ['theme' => 'business', '--image-scale' => 0.05])->assertSuccessful();
    $posts = Post::count();
    $media = Media::count();

    $this->artisan('larawebkit:demo', ['theme' => 'business', '--image-scale' => 0.05])->assertSuccessful();
    expect(Post::count())->toBe($posts)->and(Media::count())->toBe($media);

    $this->artisan('larawebkit:demo', ['theme' => 'business', '--remove' => true])->assertSuccessful();
    expect(Post::count())->toBe(0)->and(Media::where('source', 'demo')->count())->toBe(0)
        ->and(Page::where('slug', 'home')->exists())->toBeFalse();
});

it('never touches the site\'s own content', function () {
    superAdmin();
    $own = Page::factory()->create(['slug' => 'about', 'title' => 'Our own about page', 'status' => 'published', 'published_at' => now()]);

    $this->artisan('larawebkit:demo', ['theme' => 'business', '--image-scale' => 0.05])->assertFailed();

    $this->artisan('larawebkit:demo', ['theme' => 'business', '--image-scale' => 0.05, '--force' => true])->assertSuccessful();
    expect($own->fresh()->title)->toBe('Our own about page')
        ->and(Page::where('slug', 'about-demo')->exists())->toBeTrue();

    $this->artisan('larawebkit:demo', ['theme' => 'business', '--remove' => true])->assertSuccessful();
    expect($own->fresh())->not->toBeNull();
});

it('rejects unknown themes', function () {
    $this->artisan('larawebkit:demo', ['theme' => 'nope'])->assertFailed();
});

it('shows a designed 404 page with search and popular links', function () {
    Post::factory()->published()->create(['title' => 'A popular story']);

    $this->get('/does-not-exist')->assertNotFound()
        ->assertSee('lwk-system-code', false)
        ->assertSee('name="q"', false)
        ->assertSee('A popular story');
});

it('renders the 500 and maintenance pages without the database', function () {
    // Point the default connection at a database that does not exist.
    $default = config('database.default');
    config(['database.connections.broken' => ['driver' => 'sqlite', 'database' => '/nonexistent/db.sqlite'], 'database.default' => 'broken']);
    app(SettingsRepository::class)->flush();
    Cache::flush();

    $html = view('errors.500')->render();
    expect($html)->toContain(__('larawebkit-front.errors.server.title'))->toContain('lwk-system');

    $html = view('errors.503')->render();
    expect($html)->toContain(__('larawebkit-front.errors.maintenance.title'));
    config(['database.default' => $default]);
});

it('serves the maintenance page in maintenance mode', function () {
    $this->artisan('down')->assertSuccessful();

    $this->get('/')->assertStatus(503)->assertSee(__('larawebkit-front.errors.maintenance.title'));

    $this->artisan('up');
});

it('shows empty states when there is no content yet', function () {
    $this->get('/')->assertOk()->assertSee(__('larawebkit-front.no_posts'));
    $this->get('/blog')->assertOk()->assertSee('lwk-empty', false);
    $this->get('/search?q=nothing-matches')->assertOk()->assertSee(__('larawebkit-front.no_results'));
});
