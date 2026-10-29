<?php

namespace App\Modules\Blocks\Types;

use App\Modules\Blocks\Block;
use App\Modules\Blocks\BlockFields as F;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;

class Logos extends Block
{
    public static function key(): string
    {
        return 'logos';
    }

    public static function icon(): Heroicon
    {
        return Heroicon::OutlinedBuildingOffice2;
    }

    public static function variants(): array
    {
        return ['row', 'grid'];
    }

    public static function fields(): array
    {
        return [
            F::heading(),
            F::list('logos', [
                TextInput::make('name')->label(__('larawebkit-blocks.fields.name'))->required()->maxLength(80),
                F::media('image', __('larawebkit-blocks.fields.logo')),
                TextInput::make('url')->label(__('larawebkit-blocks.fields.url'))->maxLength(500),
            ], titleKey: 'name', columns: 3, max: 24),
        ];
    }

    public static function text(array $data): string
    {
        $names = array_filter(array_map(fn ($l) => $l['name'] ?? null, (array) ($data['logos'] ?? [])));

        return self::join([isset($data['heading']) ? '## '.$data['heading'] : null, $names ? implode(', ', $names) : null]);
    }
}
