<?php

namespace App\Modules\Blocks;

use Filament\Forms\Components\Builder\Block as BuilderBlock;
use Filament\Support\Icons\Heroicon;

/**
 * A section block: its admin fields (Filament Builder block), layout variants and
 * the Blade view that renders it. Views live in resources/views/components/lwk/blocks/{key}
 * and a theme can override one with themes/{name}/views/blocks/{key}.blade.php.
 * Recipe: docs/recipes/new-block.md
 */
abstract class Block
{
    abstract public static function key(): string;

    public static function icon(): string|Heroicon
    {
        return Heroicon::OutlinedSquare2Stack;
    }

    /** @return list<string> variant keys; labels in larawebkit-blocks.variants.{key}.{variant} */
    public static function variants(): array
    {
        return ['default'];
    }

    /** @return array<int, mixed> Filament form components for the block's own content */
    abstract public static function fields(): array;

    /** Whether the common background options apply (a spacer has none, for example). */
    public static function hasBackground(): bool
    {
        return true;
    }

    /** Plain text for the Markdown twin, llms-full.txt, search and the content scores. */
    public static function text(array $data): string
    {
        return self::join([$data['eyebrow'] ?? null, isset($data['heading']) ? '## '.$data['heading'] : null, $data['text'] ?? null]);
    }

    /** Extra view data (queries, lookups) computed at render time. */
    public static function prepare(array $data): array
    {
        return [];
    }

    public static function label(): string
    {
        return __('larawebkit-blocks.types.'.static::key());
    }

    public static function make(): BuilderBlock
    {
        return BuilderBlock::make(static::key())
            ->label(static::label())
            ->icon(static::icon())
            ->schema(BlockFields::wrap(static::class))
            ->columns(2);
    }

    /** @param  array<int, ?string>  $parts */
    protected static function join(array $parts): string
    {
        return trim(implode("\n\n", array_filter(array_map(fn ($p) => is_string($p) ? trim($p) : null, $parts))));
    }

    /** @param  list<array<string, mixed>>  $items */
    protected static function items(array $items, string ...$keys): string
    {
        return self::join(array_map(fn ($item) => self::join(array_map(fn ($k) => isset($item[$k]) && is_string($item[$k]) ? ($k === 'title' || $k === 'name' || $k === 'question' ? '### '.$item[$k] : $item[$k]) : null, $keys)), $items));
    }
}
