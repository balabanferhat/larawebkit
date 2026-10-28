<?php

return [

    'page_cache' => [
        // Any Laravel cache store; "file" works on every shared host.
        'store' => env('PAGE_CACHE_STORE', 'file'),
        'ttl' => (int) env('PAGE_CACHE_TTL', 3600),
    ],

    // Canonical-host / https / trailing-slash redirects are skipped in tests unless enabled.
    'enforce_in_tests' => false,

    // Outside production robots.txt disallows everything, so staging sites stay out of search.
    'block_non_production' => (bool) env('ROBOTS_BLOCK_NON_PRODUCTION', true),

    'sitemap' => [
        'max_urls' => 5000,
    ],

    'feed_items' => 20,

];
