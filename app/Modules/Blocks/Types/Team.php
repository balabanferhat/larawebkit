<?php

namespace App\Modules\Blocks\Types;

use App\Modules\Blocks\Block;
use App\Modules\Blocks\BlockFields as F;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;

class Team extends Block
{
    public static function key(): string
    {
        return 'team';
    }

    public static function icon(): Heroicon
    {
        return Heroicon::OutlinedUserGroup;
    }

    public static function variants(): array
    {
        return ['grid', 'cards'];
    }

    public static function fields(): array
    {
        return [
            F::eyebrow(),
            F::heading(),
            F::text('intro', 2),
            F::list('members', [
                TextInput::make('name')->label(__('larawebkit-blocks.fields.name'))->required()->maxLength(80),
                TextInput::make('role')->label(__('larawebkit-blocks.fields.role'))->maxLength(120),
                F::media('photo', __('larawebkit-blocks.fields.photo')),
                TextInput::make('url')->label(__('larawebkit-blocks.fields.profile_url'))->maxLength(500),
                Textarea::make('bio')->label(__('larawebkit-blocks.fields.bio'))->rows(2)->maxLength(500)->columnSpanFull(),
            ], titleKey: 'name', max: 40),
        ];
    }

    public static function text(array $data): string
    {
        return self::join([isset($data['heading']) ? '## '.$data['heading'] : null, $data['intro'] ?? null, self::items((array) ($data['members'] ?? []), 'name', 'role', 'bio')]);
    }
}
