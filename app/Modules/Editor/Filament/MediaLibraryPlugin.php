<?php

namespace App\Modules\Editor\Filament;

use Filament\Forms\Components\RichEditor\Plugins\Contracts\RichContentPlugin;
use Filament\Forms\Components\RichEditor\RichEditorTool;
use Filament\Support\Icons\Heroicon;

/**
 * Adds a "Media library" toolbar button to the RichEditor that opens the
 * WordPress-style media modal (resources/views/editor/media-modal.blade.php).
 * The modal inserts the chosen image straight into the TipTap editor.
 */
class MediaLibraryPlugin implements RichContentPlugin
{
    public static function make(): static
    {
        return new static;
    }

    public function getTipTapPhpExtensions(): array
    {
        return [];
    }

    public function getTipTapJsExtensions(): array
    {
        return [];
    }

    public function getEditorTools(): array
    {
        return [
            RichEditorTool::make('mediaLibrary')
                ->label(__('larawebkit-media.modal.open'))
                ->icon(Heroicon::OutlinedPhoto)
                ->jsHandler('window.dispatchEvent(new CustomEvent(\'lwk-media-open\', { detail: { mode: \'insert\', editor: $getEditor() } }))'),
        ];
    }

    public function getEditorActions(): array
    {
        return [];
    }
}
