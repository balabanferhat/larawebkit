<?php

namespace App\Modules\Blocks\Types;

use App\Modules\Blocks\Block;
use App\Modules\Content\Support\HtmlSanitizer;
use App\Modules\Geo\MarkdownTwin;
use Filament\Forms\Components\RichEditor;
use Filament\Support\Icons\Heroicon;

class RichText extends Block
{
    public static function key(): string
    {
        return 'rich_text';
    }

    public static function icon(): Heroicon
    {
        return Heroicon::OutlinedDocumentText;
    }

    public static function variants(): array
    {
        return ['narrow', 'wide', 'columns'];
    }

    public static function fields(): array
    {
        return [
            RichEditor::make('content')->label(__('larawebkit-editor.fields.content'))
                ->toolbarButtons([['h2', 'h3'], ['bold', 'italic', 'link'], ['bulletList', 'orderedList', 'blockquote'], ['undo', 'redo']])
                ->columnSpanFull(),
        ];
    }

    public static function text(array $data): string
    {
        return class_exists(MarkdownTwin::class) ? app(MarkdownTwin::class)->htmlToMarkdown(self::html($data)) : strip_tags(self::html($data));
    }

    public static function html(array $data): string
    {
        $html = is_string($data['content'] ?? null) ? $data['content'] : '';

        return app()->bound(HtmlSanitizer::class) ? app(HtmlSanitizer::class)->sanitize($html) : strip_tags($html, '<p><h2><h3><strong><em><a><ul><ol><li><blockquote><br>');
    }
}
