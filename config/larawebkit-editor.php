<?php

/*
|--------------------------------------------------------------------------
| larawebkit content editor
|--------------------------------------------------------------------------
|
| Scoring weights and thresholds for the editor panels. The same values are
| passed to the browser so the live score (resources/js/larawebkit/scoring.js)
| and the authoritative server score (App\Modules\Editor\Scoring) always match.
| Scores are normalised to 0-100, so changing a weight never breaks the scale.
|
*/

return [

    'autosave_seconds' => (int) env('EDITOR_AUTOSAVE_SECONDS', 30),

    'seo' => [
        'checks' => [
            'word_count' => ['enabled' => true, 'tiers' => [800 => 20, 500 => 12], 'fallback' => 5, 'max' => 20],
            'keyword_in_title' => ['enabled' => true, 'points' => 15],
            'meta_description' => ['enabled' => true, 'min' => 120, 'max' => 160, 'points' => 15, 'partial' => 8],
            'h2_count' => ['enabled' => true, 'tiers' => [4 => 15, 2 => 10], 'fallback' => 0, 'max' => 15],
            'keyword_density' => ['enabled' => true, 'min' => 1.0, 'max' => 3.0, 'points' => 15, 'partial' => 8],
            'has_list' => ['enabled' => true, 'points' => 5],
            'has_bold' => ['enabled' => true, 'points' => 5],
            'image_alt' => ['enabled' => true, 'points' => 5],
            'internal_link' => ['enabled' => true, 'points' => 5],
        ],
    ],

    'human' => [
        'start' => 100,
        'min_sentences_for_variation' => 3,
        'variation' => [
            ['below' => 0.35, 'penalty' => 25],
            ['below' => 0.5, 'penalty' => 10],
        ],
        'long_sentences' => ['average_above' => 32, 'penalty' => 10],
        'cliches' => ['penalty' => 5, 'max_penalty' => 35],
        'repeated_starts' => ['min_run' => 3, 'penalty' => 5, 'max_penalty' => 15],
        'question_bonus' => 3,
        'reader_bonus' => 4,
        // Words that address the reader directly, per locale.
        'reader_words' => [
            'en' => ['you', 'your', 'yours', 'yourself', "you're", "you'll", "you've"],
            'tr' => ['siz', 'sizin', 'size', 'sizi', 'sizde', 'sizce', 'sen', 'senin', 'sana', 'seni'],
        ],
    ],

    'geo' => [
        'checks' => [
            'has_summary' => ['enabled' => true, 'points' => 20],
            'question_h2' => ['enabled' => true, 'points' => 15],
            'faq_or_lists' => ['enabled' => true, 'points' => 20],
            'has_sources' => ['enabled' => true, 'points' => 15],
            'has_author' => ['enabled' => true, 'points' => 15],
            'answer_first' => ['enabled' => true, 'points' => 15, 'min_words' => 20, 'max_words' => 80],
        ],
    ],

    'publish_gate' => [
        'enabled' => (bool) env('EDITOR_PUBLISH_GATE', true),
        'min_seo' => 60,
        'min_words' => 300,
        'min_human' => 45,
    ],

    'html' => [
        // Allowed elements after server-side sanitizing of editor output.
        'allowed_elements' => [
            'p' => ['class'], 'br' => [], 'h2' => ['id'], 'h3' => ['id'], 'h4' => ['id'],
            'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [],
            'ul' => [], 'ol' => ['start'], 'li' => [], 'blockquote' => [], 'code' => [], 'pre' => [],
            'a' => ['href', 'title', 'target', 'rel'],
            'img' => ['src', 'alt', 'title', 'width', 'height', 'class', 'data-id', 'srcset', 'sizes', 'loading', 'decoding'],
            'figure' => ['class'], 'figcaption' => [],
            'table' => [], 'thead' => [], 'tbody' => [], 'tr' => [], 'th' => ['colspan', 'rowspan'], 'td' => ['colspan', 'rowspan'],
            'hr' => [],
        ],
    ],

];
