<?php

use App\Modules\Blocks\BlockPresets;
use App\Modules\Content\Models\Category;
use App\Modules\Content\Models\Page;
use App\Modules\Content\Models\Post;
use App\Modules\Content\Models\Tag;
use App\Modules\Settings\SettingsRepository;
use App\Modules\Themes\ThemeManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    app(SettingsRepository::class)->setMany(['general.theme' => 'magazine', 'features.page_cache' => false, 'features.newsletter' => true]);
});

/** @return array{0: Category, 1: Tag, 2: Collection<int, Post>} */
function magazineContent(int $count = 10): array
{
    $culture = Category::create(['name' => 'Culture', 'slug' => 'culture']);
    $tag = Tag::create(['name' => 'Weekend', 'slug' => 'weekend']);
    $posts = collect(range(1, $count))->map(function ($i) use ($culture, $tag) {
        $post = Post::factory()->published()->create(['title' => "Story number $i", 'slug' => "story-$i", 'published_at' => now()->subDays($i), 'excerpt' => "Excerpt $i."]);
        $post->categories()->attach($culture);
        $post->tags()->attach($tag);

        return $post;
    });

    return [$culture, $tag, $posts];
}

it('is discovered as a child of minimal with the magazine preset on offer', function () {
    $theme = app(ThemeManager::class)->get('magazine');

    expect($theme)->not->toBeNull()
        ->and(collect(app(ThemeManager::class)->chain('magazine'))->pluck('name')->all())->toBe(['magazine', 'minimal'])
        ->and(BlockPresets::options())->toHaveKey('magazine')
        ->and(collect(BlockPresets::blocks('magazine'))->pluck('type')->all())->toBe(['latest_posts', 'latest_posts', 'newsletter'])
        ->and(BlockPresets::blocks('archive'))->not->toBeEmpty()
        ->and(BlockPresets::blocks('not_found'))->not->toBeEmpty();
});

it('builds the front page from blocks: lead story, rail, category rows and the newsletter', function () {
    magazineContent();

    $html = $this->get('/')->assertOk()->assertSee('data-theme-name="magazine"', false)->getContent();

    expect($html)->toContain('lwk-block-latest-posts lwk-v-front')
        ->toContain('class="lwk-front-lead"')->toContain('class="lwk-rail"')
        ->toContain('Story number 1')->toContain('Story number 5')
        ->toContain(__('theme-magazine::theme.more_in', ['category' => 'Culture']))
        ->toContain('lwk-block-newsletter')
        ->and(substr_count($html, '<h1'))->toBe(1);
});

it('uses a page called home when the site has one', function () {
    magazineContent(3);
    Page::factory()->published()->create(['title' => 'Home', 'slug' => 'home', 'blocks' => BlockPresets::blocks('magazine')]);

    $this->get('/')->assertOk()->assertSee('lwk-v-front', false)->assertSee('Story number 1');
});

it('shows archives with the newest story large, the grid, pagination and the archive blocks', function () {
    [$culture, $tag] = magazineContent(12);

    foreach (['/blog', $culture->publicPath(), $tag->publicPath()] as $path) {
        $html = $this->get($path)->assertOk()->getContent();
        expect($html)->toContain('class="lwk-archive-lead"')->toContain('Story number 1')->toContain('lwk-block-newsletter')
            ->and(substr_count($html, 'Story number 1<'))->toBe(1)
            ->and(substr_count($html, '<h1'))->toBe(1);
    }
    $this->get('/blog?page=2')->assertOk()->assertDontSee('class="lwk-archive-lead"', false);
    $this->get('/blog/category/empty-one')->assertNotFound();
});

it('renders an article, search with and without results, and the 404 page', function () {
    [, , $posts] = magazineContent(4);

    $this->get($posts->first()->publicPath())->assertOk()->assertSee('Story number 1')->assertSee('lwk-single-reading', false);
    $this->get('/search?q=number')->assertOk()->assertSee('<mark>number</mark> 2', false)->assertDontSee(__('larawebkit-blocks.presets.not_found.latest'));
    $this->get('/search?q=nothing-here')->assertOk()->assertSee(__('larawebkit-front.no_results'))->assertSee(__('larawebkit-blocks.presets.not_found.latest'));

    $this->get('/does-not-exist')->assertNotFound()->assertSee('lwk-system-code', false)->assertSee('name="q"', false)
        ->assertSee(__('larawebkit-blocks.presets.not_found.latest'))->assertSee('Story number 1');
});

it('shows empty states and works in Turkish and in dark mode', function () {
    $this->get('/')->assertOk()->assertSee(__('larawebkit-front.no_posts'));
    $this->get('/blog')->assertOk()->assertSee('lwk-empty', false);

    app(SettingsRepository::class)->setMany(['general.default_locale' => 'tr', 'general.active_locales' => ['tr'], 'appearance.color_scheme' => 'dark']);
    app()->setLocale('tr');
    Post::factory()->published()->create(['title' => 'Yavaş hafta sonu', 'locale' => 'tr']);
    $html = $this->get('/')->assertOk()->getContent();
    expect($html)->toContain('lang="tr"')->toContain('Yavaş hafta sonu')->toContain("s='dark'");
    $this->get('/olmayan-sayfa')->assertNotFound()->assertSee(__('larawebkit-front.not_found_title', [], 'tr'));
});

it('marks every demo as made-up example content', function () {
    config(['larawebkit-themes.demo_manifest' => storage_path('framework/testing/larawebkit-demo.json')]);
    superAdmin();

    $this->artisan('larawebkit:demo', ['theme' => 'magazine', '--image-scale' => 0.05])->assertSuccessful();

    expect(setting('header.announcement'))->toBe(__('larawebkit-demo.notice'));
    $this->get('/')->assertOk()->assertSee(__('larawebkit-demo.notice'));
    File::delete(storage_path('framework/testing/larawebkit-demo.json'));
});
