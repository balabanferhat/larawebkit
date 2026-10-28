<?php

return [
    'default_instruction' => 'Improve clarity and flow.',
    'errors' => [
        'disabled' => 'The AI assistant is not configured. Add a provider key (e.g. ANTHROPIC_API_KEY) to .env.',
        'budget' => 'This month\'s AI budget has been reached.',
        'failed' => 'The AI request failed. Please try again later.',
    ],
    'actions' => [
        'draft' => 'Draft',
        'rewrite' => 'Rewrite',
        'tags' => 'Tags',
        'meta_description' => 'Meta description',
        'topics' => 'Topic ideas',
        'image_keywords' => 'Image keywords',
    ],
    'usage' => [
        'singular' => 'AI usage',
        'plural' => 'AI usage',
        'action' => 'Action',
        'provider' => 'Provider',
        'model' => 'Model',
        'input' => 'Input tokens',
        'output' => 'Output tokens',
        'cost' => 'Est. cost',
        'ok' => 'OK',
    ],
    'assistant' => [
        'title' => 'AI assistant',
        'brief' => 'Brief for the draft (optional)',
        'generate_draft' => 'Generate draft from title',
        'rewrite_selection' => 'Rewrite selection',
        'rewrite_hint' => 'Select text in the editor first.',
        'suggest_tags' => 'Suggest tags',
        'suggest_meta' => 'Suggest meta description',
        'suggest_topics' => 'Suggest topics',
        'working' => 'Working…',
        'queued' => 'Queued, waiting for the worker…',
        'replace_confirm' => 'Replace the current content with the AI draft?',
        'applied' => 'Applied',
        'budget_left' => ':spent of :budget USD used this month',
    ],
];
