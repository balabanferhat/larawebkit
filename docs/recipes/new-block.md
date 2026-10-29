# Recipe: a new section block

Pages can be built from section blocks (Filament Builder, stored as JSON in `pages.blocks`, rendered by Blade).
A block is one class plus one view.

## 1. The class

`app/Modules/Blocks/Types/Quote.php`:

```php
class Quote extends Block
{
    public static function key(): string { return 'quote'; }

    public static function icon(): Heroicon { return Heroicon::OutlinedChatBubbleBottomCenterText; }

    /** First variant is the default; labels in larawebkit-blocks.variants.quote.* */
    public static function variants(): array { return ['large', 'compact']; }

    public static function fields(): array
    {
        return [
            Textarea::make('quote')->label(__('larawebkit-blocks.fields.quote'))->required()->columnSpanFull(),
            BlockFields::media('photo', __('larawebkit-blocks.fields.photo')),
        ];
    }

    /** Plain text for the Markdown twin, llms-full.txt, word count and scores. */
    public static function text(array $data): string { return '> '.($data['quote'] ?? ''); }
}
```

Every block automatically gets the common **Layout** options (variant, background: plain / muted / brand / dark /
image, spacing, anchor id, hide on mobile). Return `false` from `hasBackground()` to skip the background options.
Override `prepare(array $data): array` to pass extra view data (queries, lookups), as `LatestPosts` does.
Media ids in keys named `image`, `photo`, `poster`, `background_image` or `images` are loaded in one query and
recorded as media usage.

## 2. Register it

Add the class to `types` in `config/larawebkit-blocks.php`, and labels to `lang/en|tr/larawebkit-blocks.php`
(`types.quote`, `variants.quote.*`).

## 3. The view

`resources/views/components/lwk/blocks/quote.blade.php` receives `$data`, `$variant`, `$media` (preloaded,
keyed by id), `$first` (first block on the page: use `<h1>` and `:priority="true"` for its image), `$index` and
`$blockId`. The wrapper `<section class="lwk-block lwk-block-quote lwk-v-{variant} lwk-bg-…">` is added for you.

```blade
<div class="lwk-container">
    <blockquote class="lwk-big-quote">{{ $data['quote'] ?? '' }}</blockquote>
</div>
```

Styles go in `themes/minimal/src/blocks.css` (then `php artisan larawebkit:themes:build`). A theme can replace the
view with `themes/{name}/views/blocks/quote.blade.php` or leave the block out via `supports.blocks` in theme.json.

## 4. Presets (optional)

Page presets ("Landing page", "About", ...) live in `App\Modules\Blocks\BlockPresets` with placeholder text in
`larawebkit-blocks.presets.*`. Add your block to a preset or add a new preset name to `presets` in the config.

## 5. Test

```php
it('renders the quote block', function () {
    Page::factory()->create(['slug' => 'q', 'status' => 'published', 'published_at' => now(), 'blocks' => [
        ['type' => 'quote', 'data' => ['quote' => 'Hello']],
    ]]);
    $this->get('/q')->assertOk()->assertSee('Hello');
});
```
