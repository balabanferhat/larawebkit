<?php

namespace App\Modules\Blocks;

use App\Modules\Media\Filament\MediaPicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;

/**
 * Shared field builders for blocks, and the common "Layout" options every
 * block gets: variant, background (plain / muted / brand / dark / image),
 * spacing, anchor id and "hide on mobile".
 */
final class BlockFields
{
    public const BACKGROUNDS = ['plain', 'muted', 'brand', 'dark', 'image'];

    public const SPACING = ['normal', 'compact', 'large'];

    /** @param  class-string<Block>  $block */
    public static function wrap(string $block): array
    {
        $variants = $block::variants();
        $layout = [];
        if (count($variants) > 1) {
            $layout[] = Select::make('variant')->label(__('larawebkit-blocks.common.variant'))->native(false)->default($variants[0])->selectablePlaceholder(false)
                ->options(collect($variants)->mapWithKeys(fn ($v) => [$v => __('larawebkit-blocks.variants.'.$block::key().'.'.$v)])->all());
        }
        if ($block::hasBackground()) {
            $layout[] = Select::make('background')->label(__('larawebkit-blocks.common.background'))->native(false)->default('plain')->selectablePlaceholder(false)->live()
                ->options(collect(self::BACKGROUNDS)->mapWithKeys(fn ($v) => [$v => __("larawebkit-blocks.backgrounds.$v")])->all());
            $layout[] = MediaPicker::make('background_image')->label(__('larawebkit-blocks.common.background_image'))
                ->visible(fn (Get $get) => $get('background') === 'image');
            $layout[] = Select::make('spacing')->label(__('larawebkit-blocks.common.spacing'))->native(false)->default('normal')->selectablePlaceholder(false)
                ->options(collect(self::SPACING)->mapWithKeys(fn ($v) => [$v => __("larawebkit-blocks.spacing.$v")])->all());
        }
        $layout[] = TextInput::make('anchor')->label(__('larawebkit-blocks.common.anchor'))->helperText(__('larawebkit-blocks.common.anchor_help'))
            ->alphaDash()->maxLength(60)->prefix('#');
        $layout[] = Toggle::make('hide_on_mobile')->label(__('larawebkit-blocks.common.hide_on_mobile'))->inline(false);

        return [
            ...$block::fields(),
            Section::make(__('larawebkit-blocks.common.layout'))->schema($layout)->columns(2)->collapsible()->collapsed()->compact()->columnSpanFull(),
        ];
    }

    public static function heading(string $name = 'heading', bool $required = false): TextInput
    {
        return TextInput::make($name)->label(__('larawebkit-blocks.fields.heading'))->maxLength(160)->required($required)->columnSpanFull();
    }

    public static function eyebrow(): TextInput
    {
        return TextInput::make('eyebrow')->label(__('larawebkit-blocks.fields.eyebrow'))->helperText(__('larawebkit-blocks.fields.eyebrow_help'))->maxLength(80);
    }

    public static function text(string $name = 'text', int $rows = 3): Textarea
    {
        return Textarea::make($name)->label(__('larawebkit-blocks.fields.text'))->rows($rows)->maxLength(2000)->columnSpanFull();
    }

    public static function media(string $name, ?string $label = null): mixed
    {
        return MediaPicker::make($name)->label($label ?? __('larawebkit-blocks.fields.image'));
    }

    public static function buttons(int $max = 2): Repeater
    {
        return Repeater::make('buttons')->label(__('larawebkit-blocks.fields.buttons'))
            ->schema([
                TextInput::make('label')->label(__('larawebkit-blocks.fields.label'))->required()->maxLength(40),
                TextInput::make('url')->label(__('larawebkit-blocks.fields.url'))->required()->maxLength(500)->placeholder('/contact'),
                Select::make('style')->label(__('larawebkit-blocks.fields.style'))->native(false)->default('primary')->selectablePlaceholder(false)
                    ->options(['primary' => __('larawebkit-blocks.button_styles.primary'), 'secondary' => __('larawebkit-blocks.button_styles.secondary'), 'link' => __('larawebkit-blocks.button_styles.link')]),
            ])
            ->columns(3)->maxItems($max)->defaultItems(0)->reorderable()->collapsible()
            ->itemLabel(fn (array $state) => $state['label'] ?? null)
            ->addActionLabel(__('larawebkit-blocks.fields.add_button'))
            ->columnSpanFull();
    }

    /** @param  array<int, mixed>  $schema */
    public static function list(string $name, array $schema, string $titleKey = 'title', int $columns = 2, ?int $max = 24): Repeater
    {
        return Repeater::make($name)->label(__("larawebkit-blocks.fields.$name"))
            ->schema($schema)->columns($columns)->maxItems($max)->defaultItems(0)->reorderable()->collapsible()->cloneable()
            ->itemLabel(fn (array $state) => $state[$titleKey] ?? null)
            ->addActionLabel(__('larawebkit-blocks.fields.add_item'))
            ->columnSpanFull();
    }
}
