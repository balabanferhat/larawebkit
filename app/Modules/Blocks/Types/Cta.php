<?php

namespace App\Modules\Blocks\Types;

use App\Modules\Blocks\Block;
use App\Modules\Blocks\BlockFields as F;
use Filament\Support\Icons\Heroicon;

class Cta extends Block
{
    public static function key(): string
    {
        return 'cta';
    }

    public static function icon(): Heroicon
    {
        return Heroicon::OutlinedMegaphone;
    }

    public static function variants(): array
    {
        return ['banner', 'boxed', 'split'];
    }

    public static function fields(): array
    {
        return [F::heading(required: true), F::text(), F::buttons(), F::media('image')->helperText(__('larawebkit-blocks.help.cta_image'))];
    }
}
