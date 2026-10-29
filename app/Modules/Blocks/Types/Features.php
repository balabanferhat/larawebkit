<?php

namespace App\Modules\Blocks\Types;

use App\Modules\Blocks\Block;
use App\Modules\Blocks\BlockFields as F;
use App\Modules\Blocks\BlockIcons;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;

class Features extends Block
{
    public static function key(): string
    {
        return 'features';
    }

    public static function icon(): Heroicon
    {
        return Heroicon::OutlinedSquares2x2;
    }

    public static function variants(): array
    {
        return ['grid', 'cards', 'list', 'image'];
    }

    public static function fields(): array
    {
        return [
            F::eyebrow(),
            F::heading(),
            F::text('intro', 2),
            F::list('items', [
                Select::make('icon')->label(__('larawebkit-blocks.fields.icon'))->options(BlockIcons::options())->searchable()->native(false),
                TextInput::make('title')->label(__('larawebkit-blocks.fields.title'))->required()->maxLength(120),
                Textarea::make('text')->label(__('larawebkit-blocks.fields.text'))->rows(2)->maxLength(600)->columnSpanFull(),
                TextInput::make('url')->label(__('larawebkit-blocks.fields.url'))->maxLength(500),
                TextInput::make('link_label')->label(__('larawebkit-blocks.fields.link_label'))->maxLength(40),
            ]),
            F::media('image')->helperText(__('larawebkit-blocks.help.features_image')),
        ];
    }

    public static function text(array $data): string
    {
        return self::join([isset($data['heading']) ? '## '.$data['heading'] : null, $data['intro'] ?? null, self::items((array) ($data['items'] ?? []), 'title', 'text')]);
    }
}
