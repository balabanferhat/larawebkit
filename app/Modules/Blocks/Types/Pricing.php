<?php

namespace App\Modules\Blocks\Types;

use App\Modules\Blocks\Block;
use App\Modules\Blocks\BlockFields as F;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Support\Icons\Heroicon;

/** Display only: plans link to a page or form. Payments come in v0.3. */
class Pricing extends Block
{
    public static function key(): string
    {
        return 'pricing';
    }

    public static function icon(): Heroicon
    {
        return Heroicon::OutlinedTag;
    }

    public static function variants(): array
    {
        return ['cards', 'compact'];
    }

    public static function fields(): array
    {
        return [
            F::eyebrow(),
            F::heading(),
            F::text('intro', 2),
            F::list('plans', [
                TextInput::make('name')->label(__('larawebkit-blocks.fields.name'))->required()->maxLength(60),
                TextInput::make('price')->label(__('larawebkit-blocks.fields.price'))->maxLength(30)->placeholder('₺990'),
                TextInput::make('period')->label(__('larawebkit-blocks.fields.period'))->maxLength(30),
                TextInput::make('description')->label(__('larawebkit-blocks.fields.description'))->maxLength(200),
                Textarea::make('features')->label(__('larawebkit-blocks.fields.features'))->helperText(__('larawebkit-blocks.help.one_per_line'))->rows(4)->columnSpanFull(),
                TextInput::make('button_label')->label(__('larawebkit-blocks.fields.label'))->maxLength(40),
                TextInput::make('button_url')->label(__('larawebkit-blocks.fields.url'))->maxLength(500),
                Toggle::make('highlighted')->label(__('larawebkit-blocks.fields.highlighted')),
                TextInput::make('badge')->label(__('larawebkit-blocks.fields.badge'))->maxLength(30),
            ], titleKey: 'name', max: 4),
            TextInput::make('note')->label(__('larawebkit-blocks.fields.note'))->maxLength(200)->columnSpanFull(),
        ];
    }

    public static function text(array $data): string
    {
        return self::join([
            isset($data['heading']) ? '## '.$data['heading'] : null,
            $data['intro'] ?? null,
            ...array_map(fn ($p) => self::join(['### '.($p['name'] ?? '').(filled($p['price'] ?? null) ? ' — '.$p['price'].' '.($p['period'] ?? '') : ''), $p['description'] ?? null, implode("\n", array_map(fn ($l) => '- '.trim($l), array_filter(preg_split('/\R/', (string) ($p['features'] ?? '')))))]), (array) ($data['plans'] ?? [])),
        ]);
    }
}
