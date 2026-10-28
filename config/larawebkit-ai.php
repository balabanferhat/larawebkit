<?php

/*
|--------------------------------------------------------------------------
| AI layer (optional)
|--------------------------------------------------------------------------
|
| Providers are tried in `order`; the first one with a key is used and the
| next ones are fallbacks. Without any key every AI action is hidden.
| Each action picks a model tier: "cheap" for tags/keywords, "strong" for drafts.
| Prices are USD per million tokens and only used to estimate the monthly
| budget (Settings → AI). Recipe: docs/recipes/new-ai-action.md
|
*/

return [

    'order' => array_filter(explode(',', (string) env('AI_PROVIDERS', 'anthropic,openai,deepseek,ollama'))),

    'providers' => [
        'anthropic' => [
            'driver' => 'anthropic',
            'api_key' => env('ANTHROPIC_API_KEY'),
            'models' => [
                'cheap' => env('ANTHROPIC_MODEL_CHEAP', 'claude-haiku-5-5'),
                'strong' => env('ANTHROPIC_MODEL_STRONG', 'claude-opus-5-5'),
            ],
        ],
        'openai' => [
            'driver' => 'openai',
            'api_key' => env('OPENAI_API_KEY'),
            'base_url' => env('OPENAI_BASE_URL'),
            'models' => [
                'cheap' => env('OPENAI_MODEL_CHEAP', 'gpt-4o-mini'),
                'strong' => env('OPENAI_MODEL_STRONG', 'gpt-4o'),
            ],
        ],
        'deepseek' => [
            'driver' => 'openai',
            'api_key' => env('DEEPSEEK_API_KEY'),
            'base_url' => env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com/v1'),
            'models' => [
                'cheap' => env('DEEPSEEK_MODEL_CHEAP', 'deepseek-chat'),
                'strong' => env('DEEPSEEK_MODEL_STRONG', 'deepseek-chat'),
            ],
        ],
        // Local OpenAI-compatible server (Ollama, LM Studio, vLLM). Enabled when OLLAMA_BASE_URL is set.
        'ollama' => [
            'driver' => 'openai',
            'api_key' => env('OLLAMA_BASE_URL') ? (env('OLLAMA_API_KEY') ?: 'ollama') : null,
            'base_url' => env('OLLAMA_BASE_URL'),
            'models' => [
                'cheap' => env('OLLAMA_MODEL', 'llama3.1'),
                'strong' => env('OLLAMA_MODEL', 'llama3.1'),
            ],
        ],
    ],

    // USD per 1M tokens [input, output]. Unknown models count as free (e.g. local).
    'pricing' => [
        'claude-opus-5-5' => [4.00, 20.00],
        'claude-sonnet-5-5' => [2.00, 10.00],
        'claude-haiku-5-5' => [0.10, 0.50],
        'gpt-4o' => [2.50, 10.00],
        'gpt-4o-mini' => [0.15, 0.60],
        'deepseek-chat' => [0.27, 1.10],
    ],

    'actions' => [
        'draft' => ['tier' => 'strong', 'max_tokens' => 8000, 'queue' => true],
        'rewrite' => ['tier' => 'strong', 'max_tokens' => 4000, 'queue' => false],
        'tags' => ['tier' => 'cheap', 'max_tokens' => 300, 'queue' => false],
        'meta_description' => ['tier' => 'cheap', 'max_tokens' => 300, 'queue' => false],
        'topics' => ['tier' => 'cheap', 'max_tokens' => 800, 'queue' => false],
        'image_keywords' => ['tier' => 'cheap', 'max_tokens' => 100, 'queue' => false],
    ],

    // Searched in order; put your own versions in resources/prompts/overrides.
    'prompt_paths' => [
        resource_path('prompts/overrides'),
        resource_path('prompts'),
    ],

    'timeout' => (int) env('AI_TIMEOUT', 120),

];
