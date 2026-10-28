<?php

/*
|--------------------------------------------------------------------------
| GEO: visibility in AI search (ChatGPT, Claude, Perplexity, Gemini, ...)
|--------------------------------------------------------------------------
|
| purpose: "search" = fetches pages to answer a user's question or build a
| search index (you usually want these), "training" = collects data to train
| models. Presets in the admin (Settings → GEO) allow/deny by purpose.
|
*/

return [

    'bots' => [
        'GPTBot' => ['purpose' => 'training', 'operator' => 'OpenAI'],
        'OAI-SearchBot' => ['purpose' => 'search', 'operator' => 'OpenAI'],
        'ChatGPT-User' => ['purpose' => 'search', 'operator' => 'OpenAI'],
        'ClaudeBot' => ['purpose' => 'training', 'operator' => 'Anthropic'],
        'Claude-SearchBot' => ['purpose' => 'search', 'operator' => 'Anthropic'],
        'Claude-User' => ['purpose' => 'search', 'operator' => 'Anthropic'],
        'PerplexityBot' => ['purpose' => 'search', 'operator' => 'Perplexity'],
        'Perplexity-User' => ['purpose' => 'search', 'operator' => 'Perplexity'],
        'Google-Extended' => ['purpose' => 'training', 'operator' => 'Google'],
        'Applebot-Extended' => ['purpose' => 'training', 'operator' => 'Apple'],
        'CCBot' => ['purpose' => 'training', 'operator' => 'Common Crawl'],
        'Bytespider' => ['purpose' => 'training', 'operator' => 'ByteDance'],
        'Meta-ExternalAgent' => ['purpose' => 'training', 'operator' => 'Meta'],
        'Amazonbot' => ['purpose' => 'search', 'operator' => 'Amazon'],
        'DuckAssistBot' => ['purpose' => 'search', 'operator' => 'DuckDuckGo'],
        'MistralAI-User' => ['purpose' => 'search', 'operator' => 'Mistral'],
    ],

    // Referrer hosts counted as "referrals from AI assistants" in the analytics dashboard.
    'ai_referrers' => [
        'chatgpt.com' => 'ChatGPT',
        'chat.openai.com' => 'ChatGPT',
        'perplexity.ai' => 'Perplexity',
        'www.perplexity.ai' => 'Perplexity',
        'claude.ai' => 'Claude',
        'gemini.google.com' => 'Gemini',
        'copilot.microsoft.com' => 'Copilot',
        'www.bing.com/chat' => 'Copilot',
        'chat.mistral.ai' => 'Mistral',
    ],

    'llms_full_max_items' => 500,

];
