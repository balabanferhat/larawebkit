<?php

namespace App\Modules\Blocks\Types;

use App\Modules\Blocks\Block;
use App\Modules\Blocks\BlockFields as F;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;

class Stats extends Block
{
    public static function key(): string
    {
        return 'stats';
    }

    public static function icon(): Heroicon
    {
        return Heroicon::OutlinedChartBar;
    }

    public static function variants(): array
    {
        return ['row', 'cards'];
    }

    public static function fields(): array
    {
        return [
            F::heading(),
            F::text('intro', 2),
            F::list('items', [
                TextInput::make('value')->label(__('larawebkit-blocks.fields.value'))->required()->maxLength(20)->placeholder('98%'),
                TextInput::make('label')->label(__('larawebkit-blocks.fields.label'))->required()->maxLength(80),
            ], titleKey: 'label', max: 6),
        ];
    }

    public static function text(array $data): string
    {
        return self::join([isset($data['heading']) ? '## '.$data['heading'] : null, $data['intro'] ?? null, implode("\n", array_map(fn ($i) => '- '.($i['value'] ?? '').' '.($i['label'] ?? ''), (array) ($data['items'] ?? [])))]);
    }
}
