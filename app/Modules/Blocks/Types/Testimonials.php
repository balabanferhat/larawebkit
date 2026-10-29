<?php

namespace App\Modules\Blocks\Types;

use App\Modules\Blocks\Block;
use App\Modules\Blocks\BlockFields as F;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;

class Testimonials extends Block
{
    public static function key(): string
    {
        return 'testimonials';
    }

    public static function icon(): Heroicon
    {
        return Heroicon::OutlinedChatBubbleLeftRight;
    }

    public static function variants(): array
    {
        return ['grid', 'single', 'scroll'];
    }

    public static function fields(): array
    {
        return [
            F::eyebrow(),
            F::heading(),
            F::list('items', [
                Textarea::make('quote')->label(__('larawebkit-blocks.fields.quote'))->required()->rows(3)->maxLength(1000)->columnSpanFull(),
                TextInput::make('name')->label(__('larawebkit-blocks.fields.name'))->required()->maxLength(80),
                TextInput::make('role')->label(__('larawebkit-blocks.fields.role'))->maxLength(120),
                F::media('photo', __('larawebkit-blocks.fields.photo')),
                Select::make('rating')->label(__('larawebkit-blocks.fields.rating'))->options([5 => '★★★★★', 4 => '★★★★', 3 => '★★★'])->native(false),
            ], titleKey: 'name'),
        ];
    }

    public static function text(array $data): string
    {
        return self::join([
            isset($data['heading']) ? '## '.$data['heading'] : null,
            ...array_map(fn ($i) => '> '.trim((string) ($i['quote'] ?? ''))."\n> — ".trim(($i['name'] ?? '').(filled($i['role'] ?? null) ? ', '.$i['role'] : '')), (array) ($data['items'] ?? [])),
        ]);
    }
}
