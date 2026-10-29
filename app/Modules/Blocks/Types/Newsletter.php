<?php

namespace App\Modules\Blocks\Types;

use App\Modules\Blocks\Block;
use App\Modules\Blocks\BlockFields as F;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;

/** Hidden on the site while the newsletter module or Settings → Features → Newsletter is off. */
class Newsletter extends Block
{
    public static function key(): string
    {
        return 'newsletter';
    }

    public static function icon(): Heroicon
    {
        return Heroicon::OutlinedEnvelope;
    }

    public static function variants(): array
    {
        return ['card', 'inline', 'split'];
    }

    public static function fields(): array
    {
        return [
            F::heading(),
            F::text(),
            TextInput::make('button')->label(__('larawebkit-blocks.fields.button_label'))->maxLength(40),
            F::media('image')->helperText(__('larawebkit-blocks.help.newsletter_image')),
        ];
    }
}
