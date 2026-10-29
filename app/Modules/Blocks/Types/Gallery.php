<?php

namespace App\Modules\Blocks\Types;

use App\Modules\Blocks\Block;
use App\Modules\Blocks\BlockFields as F;
use App\Modules\Media\Filament\MediaPicker;
use Filament\Forms\Components\Select;
use Filament\Support\Icons\Heroicon;

/** Images from the media library (already WebP variants), opened in a lightbox. */
class Gallery extends Block
{
    public static function key(): string
    {
        return 'gallery';
    }

    public static function icon(): Heroicon
    {
        return Heroicon::OutlinedPhoto;
    }

    public static function variants(): array
    {
        return ['grid', 'masonry'];
    }

    public static function fields(): array
    {
        return [
            F::heading(),
            F::text('intro', 2),
            MediaPicker::make('images')->label(__('larawebkit-blocks.fields.images'))->multiple()->maxItems(40)->columnSpanFull(),
            Select::make('columns')->label(__('larawebkit-blocks.fields.columns'))->options([2 => '2', 3 => '3', 4 => '4'])->default(3)->native(false)->selectablePlaceholder(false),
        ];
    }
}
