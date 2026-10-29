<?php

namespace App\Modules\Blocks\Types;

use App\Modules\Blocks\Block;
use App\Modules\Blocks\BlockFields as F;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

class Hero extends Block
{
    public static function key(): string
    {
        return 'hero';
    }

    public static function icon(): Heroicon
    {
        return Heroicon::OutlinedSparkles;
    }

    public static function variants(): array
    {
        return ['split', 'centered', 'background', 'video', 'portrait'];
    }

    public static function fields(): array
    {
        return [
            F::eyebrow(),
            F::heading(required: true),
            F::text(),
            F::buttons(),
            F::media('image')->helperText(__('larawebkit-blocks.help.hero_image')),
            TextInput::make('video_url')->label(__('larawebkit-blocks.fields.video_url'))->url()->maxLength(500)
                ->helperText(__('larawebkit-blocks.help.video_url'))
                ->visible(fn (Get $get) => $get('variant') === 'video'),
            TextInput::make('note')->label(__('larawebkit-blocks.fields.note'))->helperText(__('larawebkit-blocks.help.note'))->maxLength(120),
        ];
    }

    public static function text(array $data): string
    {
        return self::join([$data['eyebrow'] ?? null, isset($data['heading']) ? '# '.$data['heading'] : null, $data['text'] ?? null]);
    }
}
