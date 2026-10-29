<?php

namespace App\Modules\Blocks\Types;

use App\Modules\Blocks\Block;
use App\Modules\Blocks\BlockFields as F;
use App\Modules\Blocks\VideoEmbed;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;

/** YouTube (youtube-nocookie.com) or Vimeo (dnt=1), loaded only after a click. */
class Video extends Block
{
    public static function key(): string
    {
        return 'video';
    }

    public static function icon(): Heroicon
    {
        return Heroicon::OutlinedPlayCircle;
    }

    public static function variants(): array
    {
        return ['wide', 'narrow'];
    }

    public static function fields(): array
    {
        return [
            F::heading(),
            F::text('intro', 2),
            TextInput::make('url')->label(__('larawebkit-blocks.fields.video_url'))->required()->url()->maxLength(500)
                ->helperText(__('larawebkit-blocks.help.video_url'))
                ->rule(fn () => fn (string $attribute, $value, $fail) => VideoEmbed::parse((string) $value) ? null : $fail(__('larawebkit-blocks.help.video_url')))
                ->columnSpanFull(),
            F::media('poster', __('larawebkit-blocks.fields.poster'))->helperText(__('larawebkit-blocks.help.poster')),
            TextInput::make('caption')->label(__('larawebkit-blocks.fields.caption'))->maxLength(200),
        ];
    }

    public static function text(array $data): string
    {
        return self::join([isset($data['heading']) ? '## '.$data['heading'] : null, $data['intro'] ?? null, filled($data['url'] ?? null) ? '['.($data['caption'] ?? $data['heading'] ?? 'Video').']('.$data['url'].')' : null]);
    }
}
