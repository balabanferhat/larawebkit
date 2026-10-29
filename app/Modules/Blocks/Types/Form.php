<?php

namespace App\Modules\Blocks\Types;

use App\Modules\Blocks\Block;
use App\Modules\Blocks\BlockFields as F;
use App\Modules\Blocks\BlockForms;
use Filament\Forms\Components\Select;
use Filament\Support\Icons\Heroicon;

/**
 * Embeds a form. v0.2-A ships the contact form; the form builder (v0.2-B)
 * adds its forms to the "form" select through BlockForms::register().
 */
class Form extends Block
{
    public static function key(): string
    {
        return 'form';
    }

    public static function icon(): Heroicon
    {
        return Heroicon::OutlinedPencilSquare;
    }

    public static function variants(): array
    {
        return ['split', 'centered'];
    }

    public static function fields(): array
    {
        return [
            F::eyebrow(),
            F::heading(),
            F::text(),
            Select::make('form')->label(__('larawebkit-blocks.fields.form'))->options(fn () => BlockForms::options())->default('contact')->native(false)->required(),
            F::media('image')->helperText(__('larawebkit-blocks.help.form_image')),
        ];
    }
}
