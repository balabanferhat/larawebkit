<?php

namespace App\Modules\Blocks\Types;

use App\Modules\Blocks\Block;
use App\Modules\Blocks\BlockFields as F;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;

/** FAQ items also feed the page's FAQPage structured data and its Markdown twin. */
class Faq extends Block
{
    public static function key(): string
    {
        return 'faq';
    }

    public static function icon(): Heroicon
    {
        return Heroicon::OutlinedQuestionMarkCircle;
    }

    public static function variants(): array
    {
        return ['accordion', 'columns'];
    }

    public static function fields(): array
    {
        return [
            F::heading(),
            F::text('intro', 2),
            F::list('items', [
                TextInput::make('question')->label(__('larawebkit-editor.fields.question'))->required()->maxLength(300)->columnSpanFull(),
                Textarea::make('answer')->label(__('larawebkit-editor.fields.answer'))->required()->rows(3)->maxLength(3000)->columnSpanFull(),
            ], titleKey: 'question', columns: 1, max: 40),
        ];
    }

    public static function text(array $data): string
    {
        return self::join([isset($data['heading']) ? '## '.$data['heading'] : null, $data['intro'] ?? null, self::items((array) ($data['items'] ?? []), 'question', 'answer')]);
    }

    /** @return list<array{question: string, answer: string}> */
    public static function questions(array $data): array
    {
        return array_values(array_filter(array_map(fn ($i) => ['question' => trim((string) ($i['question'] ?? '')), 'answer' => trim((string) ($i['answer'] ?? ''))], (array) ($data['items'] ?? [])), fn ($i) => $i['question'] !== '' && $i['answer'] !== ''));
    }
}
