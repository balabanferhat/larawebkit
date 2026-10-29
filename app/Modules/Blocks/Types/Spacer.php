<?php

namespace App\Modules\Blocks\Types;

use App\Modules\Blocks\Block;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Support\Icons\Heroicon;

class Spacer extends Block
{
    public static function key(): string
    {
        return 'spacer';
    }

    public static function icon(): Heroicon
    {
        return Heroicon::OutlinedArrowsUpDown;
    }

    public static function hasBackground(): bool
    {
        return false;
    }

    public static function fields(): array
    {
        return [
            Select::make('size')->label(__('larawebkit-blocks.fields.size'))->native(false)->default('md')->selectablePlaceholder(false)
                ->options(['sm' => __('larawebkit-blocks.sizes.sm'), 'md' => __('larawebkit-blocks.sizes.md'), 'lg' => __('larawebkit-blocks.sizes.lg')]),
            Toggle::make('divider')->label(__('larawebkit-blocks.fields.divider'))->inline(false),
        ];
    }

    public static function text(array $data): string
    {
        return '';
    }
}
