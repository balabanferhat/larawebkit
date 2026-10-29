<?php

namespace App\Modules\Blocks\Filament;

use App\Modules\Blocks\BlockPresets;
use App\Modules\Blocks\BlockRegistry;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;

/** The "Sections" builder in the page editor, plus the preset picker on create. */
final class BlocksField
{
    public static function make(string $name = 'blocks'): Builder
    {
        $registry = app(BlockRegistry::class);

        return Builder::make($name)
            ->label(__('larawebkit-blocks.field.label'))
            ->helperText(__('larawebkit-blocks.field.help'))
            ->blocks(array_values(array_map(fn ($class) => $class::make(), $registry->available())))
            ->blockPickerColumns(['default' => 2, 'lg' => 3])
            ->blockPickerWidth('3xl')
            ->collapsible()
            ->cloneable()
            ->reorderableWithButtons()
            ->addActionLabel(__('larawebkit-blocks.field.add'))
            ->columnSpanFull();
    }

    public static function preset(string $target = 'blocks'): Select
    {
        return Select::make('preset')
            ->label(__('larawebkit-blocks.field.preset'))
            ->helperText(__('larawebkit-blocks.field.preset_help'))
            ->options(BlockPresets::options())
            ->placeholder(__('larawebkit-blocks.field.preset_blank'))
            ->native(false)
            ->live()
            ->dehydrated(false)
            ->visibleOn('create')
            ->afterStateUpdated(function (?string $state, Get $get, Set $set) use ($target) {
                if ($state && blank($get($target))) {
                    $blocks = [];
                    foreach (BlockPresets::blocks($state) as $block) {
                        $blocks[(string) Str::uuid()] = $block;
                    }
                    $set($target, $blocks);
                }
            });
    }
}
