<?php

use App\Modules\Blocks\BlockPresets;
use App\Modules\Blocks\BlockRegistry;
use App\Modules\Blocks\VideoEmbed;
use App\Modules\Content\Filament\Resources\Pages\Pages\CreatePage;
use App\Modules\Content\Models\Page;
use App\Modules\Content\Models\Post;
use App\Modules\Media\ImagePipeline;
use App\Modules\Media\Models\MediaUsage;
use App\Modules\Settings\SettingsRepository;
use App\Modules\Themes\ThemeManager;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function blockPage(array $blocks, array $attributes = []): Page
{
    return Page::factory()->create(['slug' => 'landing', 'status' => 'published', 'published_at' => now()->subDay(), 'content' => '', 'blocks' => $blocks] + $attributes);
}

/** One example of every block type, with the fields a visitor would see. */
function everyBlock(int $imageId = 0): array
{
    return [
        ['type' => 'hero', 'data' => ['variant' => 'split', 'eyebrow' => 'New', 'heading' => 'Hero heading', 'text' => 'Hero text', 'image' => $imageId, 'buttons' => [['label' => 'Start', 'url' => '/contact', 'style' => 'primary']]]],
        ['type' => 'features', 'data' => ['heading' => 'Features heading', 'items' => [['title' => 'Fast', 'text' => 'Very fast', 'icon' => 'bolt']]]],
        ['type' => 'cta', 'data' => ['variant' => 'banner', 'background' => 'brand', 'heading' => 'Call heading', 'buttons' => [['label' => 'Go', 'url' => '/go']]]],
        ['type' => 'testimonials', 'data' => ['heading' => 'Quotes', 'items' => [['quote' => 'Great work', 'name' => 'Ada', 'role' => 'CTO', 'rating' => 5]]]],
        ['type' => 'faq', 'data' => ['heading' => 'FAQ heading', 'items' => [['question' => 'Block question?', 'answer' => 'Block answer.']]]],
        ['type' => 'pricing', 'data' => ['heading' => 'Pricing heading', 'plans' => [['name' => 'Pro', 'price' => '€9', 'period' => 'month', 'features' => "One\nTwo", 'button_label' => 'Buy', 'button_url' => '/buy', 'highlighted' => true]]]],
        ['type' => 'team', 'data' => ['heading' => 'Team heading', 'members' => [['name' => 'Grace', 'role' => 'Engineer']]]],
        ['type' => 'logos', 'data' => ['heading' => 'Logos heading', 'logos' => [['name' => 'Acme']]]],
        ['type' => 'stats', 'data' => ['items' => [['value' => '99%', 'label' => 'uptime']]]],
        ['type' => 'gallery', 'data' => ['heading' => 'Gallery heading', 'images' => $imageId ? [$imageId] : []]],
        ['type' => 'video', 'data' => ['heading' => 'Video heading', 'url' => 'https://youtu.be/dQw4w9WgXcQ']],
        ['type' => 'rich_text', 'data' => ['content' => '<h2>Rich heading</h2><p>Rich text <script>alert(1)</script></p>']],
        ['type' => 'newsletter', 'data' => ['heading' => 'Newsletter heading']],
        ['type' => 'form', 'data' => ['heading' => 'Form heading', 'form' => 'contact']],
        ['type' => 'latest_posts', 'data' => ['heading' => 'Latest heading', 'count' => 3]],
        ['type' => 'spacer', 'data' => ['size' => 'lg', 'divider' => true, 'hide_on_mobile' => true]],
    ];
}

it('registers sixteen block types with translated labels', function () {
    $types = app(BlockRegistry::class)->all();

    expect($types)->toHaveCount(16);
    foreach ($types as $key => $class) {
        expect(__("larawebkit-blocks.types.$key"))->not->toBe("larawebkit-blocks.types.$key");
        foreach ($class::variants() as $variant) {
            if (count($class::variants()) > 1) {
                expect(__("larawebkit-blocks.variants.$key.$variant"))->not->toStartWith('larawebkit-blocks.');
            }
        }
    }
});

it('renders every block type on a page', function () {
    Storage::fake('public');
    Storage::fake('local');
    $image = app(ImagePipeline::class)->store(fakeJpeg(800, 600), 'photo.jpg', ['alt' => 'A photo']);
    Post::factory()->create(['title' => 'A recent post', 'status' => 'published', 'published_at' => now()->subHour()]);
    blockPage(everyBlock($image->id), ['title' => 'Blocks page']);

    $html = $this->get('/landing')->assertOk()
        ->assertSee('<h1 class="lwk-hero-title">Hero heading</h1>', false)
        ->assertSee(['Features heading', 'Call heading', 'Great work', 'Block question?', '€9', 'Grace', 'Acme', '99%', 'Gallery heading', 'Rich heading', 'Form heading', 'A recent post'])
        ->assertSee('lwk-bg-brand', false)
        ->assertSee('lwk-hide-mobile', false)
        ->assertSee('data-lightbox', false)
        ->assertSee('rel="preload" as="image"', false)
        ->assertDontSee('<script>alert(1)</script>', false)
        ->getContent();

    // Only one h1 on the page: the first hero carries it, the title is not repeated.
    expect(substr_count($html, '<h1'))->toBe(1)
        // Video: nothing from YouTube is loaded before the click.
        ->and($html)->toContain('data-video-src="https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ')
        ->and($html)->not->toContain('<iframe');
});

it('shows the page title when the first block is not a hero', function () {
    blockPage([['type' => 'rich_text', 'data' => ['content' => '<p>Hello</p>']]], ['title' => 'Plain title']);

    $this->get('/landing')->assertOk()->assertSee('<h1>Plain title</h1>', false);
});

it('feeds FAQ blocks into FAQPage schema and the Markdown twin', function () {
    $page = blockPage(everyBlock(), ['faq' => [['question' => 'Page question?', 'answer' => 'Page answer.']]]);

    $html = $this->get('/landing')->getContent();
    preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
    $faq = collect(json_decode($m[1], true)['@graph'])->firstWhere('@type', 'FAQPage');
    expect(collect($faq['mainEntity'])->pluck('name')->all())->toBe(['Page question?', 'Block question?']);

    $md = $this->get('/landing.md')->assertOk()->getContent();
    expect($md)->toContain('# Hero heading')->toContain('### Fast')->toContain('Block answer.')
        ->and(substr_count($md, 'Block question?'))->toBe(1);
});

it('counts block text for words and scores', function () {
    $page = blockPage(everyBlock());

    expect($page->word_count)->toBeGreaterThan(30)
        ->and($page->seo_score)->not->toBeNull();
});

it('tracks images used in blocks so they are not deleted by accident', function () {
    Storage::fake('public');
    Storage::fake('local');
    $image = app(ImagePipeline::class)->store(fakeJpeg(400, 300), 'photo.jpg');
    blockPage([['type' => 'gallery', 'data' => ['images' => [$image->id]]]]);

    expect(MediaUsage::where('media_id', $image->id)->where('context', 'blocks')->exists())->toBeTrue();
});

it('parses YouTube and Vimeo URLs into privacy-friendly embeds', function () {
    expect(VideoEmbed::parse('https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=10')['embed'])->toStartWith('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ')
        ->and(VideoEmbed::parse('https://youtu.be/dQw4w9WgXcQ')['provider'])->toBe('youtube')
        ->and(VideoEmbed::parse('https://vimeo.com/76979871')['embed'])->toContain('dnt=1')
        ->and(VideoEmbed::parse('https://evil.example.com/watch?v=dQw4w9WgXcQ'))->toBeNull()
        ->and(VideoEmbed::parse('javascript:alert(1)'))->toBeNull();
});

it('renders every preset in both languages', function (string $locale) {
    app()->setLocale($locale);
    foreach (array_keys(BlockPresets::options()) as $preset) {
        $blocks = BlockPresets::blocks($preset);
        expect($blocks)->not->toBeEmpty()
            ->and(json_encode($blocks))->not->toContain('larawebkit-blocks.presets');
        Page::query()->forceDelete();
        blockPage($blocks);
        $this->get('/landing')->assertOk();
    }
})->with(['en', 'tr']);

it('fills the sections from a preset when creating a page', function () {
    $this->actingAs(superAdmin());

    Livewire::test(CreatePage::class)
        ->fillForm(['preset' => 'landing'])
        ->assertSchemaStateSet(fn (array $state) => expect(collect($state['blocks'])->pluck('type')->all())->toBe(collect(BlockPresets::blocks('landing'))->pluck('type')->all()) ? [] : []);
});

it('lets a theme limit blocks and override a block view', function () {
    $root = storage_path('framework/testing/themes-'.uniqid());
    File::copyDirectory(base_path('themes/minimal'), "$root/minimal");
    File::ensureDirectoryExists("$root/slim/views/blocks");
    File::put("$root/slim/theme.json", json_encode(['name' => 'Slim', 'parent' => 'minimal', 'supports' => ['blocks' => ['hero', 'rich_text']]]));
    File::put("$root/slim/views/blocks/hero.blade.php", '<div class="slim-hero">{{ $data[\'heading\'] }}</div>');
    config(['larawebkit-themes.path' => $root]);
    app(ThemeManager::class)->flush();
    app(SettingsRepository::class)->set('general.theme', 'slim');

    expect(array_keys(app(BlockRegistry::class)->available()))->toBe(['hero', 'rich_text']);
    blockPage([['type' => 'hero', 'data' => ['heading' => 'Slim hero']]]);
    $this->get('/landing')->assertOk()->assertSee('<div class="slim-hero">Slim hero</div>', false);

    File::deleteDirectory($root);
});

it('drops unknown block types and survives the module being disabled', function () {
    app(SettingsRepository::class)->set('features.page_cache', false);
    blockPage([['type' => 'nope', 'data' => []], ['type' => 'rich_text', 'data' => ['content' => '<p>Kept</p>']]]);
    $this->get('/landing')->assertOk()->assertSee('Kept');

    config(['larawebkit.modules' => collect(config('larawebkit.modules'))->except('blocks')->all()]);
    $this->get('/landing')->assertOk()->assertDontSee('Kept');
});
