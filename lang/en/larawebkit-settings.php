<?php

return [
    'title' => 'Settings',
    'saved' => 'Settings saved.',
    'groups' => [
        'general' => 'General',
        'social' => 'Social',
        'organization' => 'Organization',
        'analytics' => 'Analytics',
        'seo' => 'SEO',
        'geo' => 'AI search (GEO)',
        'privacy' => 'Privacy',
        'features' => 'Features',
        'ai' => 'AI',
    ],
    'keys' => [
        'general' => [
            'site_name' => 'Site name',
            'tagline' => 'Tagline',
            'logo' => 'Logo',
            'contact_email' => 'Contact e-mail',
            'default_locale' => 'Default language',
            'active_locales' => 'Active languages',
            'theme' => 'Theme',
            'posts_per_page' => 'Posts per page',
        ],
        'social' => [
            'links' => 'Social profiles',
        ],
        'organization' => [
            'type' => 'The site represents',
            'name' => 'Organization / person name',
            'logo' => 'Organization logo',
            'phone' => 'Phone',
            'address' => 'Address',
        ],
        'analytics' => [
            'google_analytics_id' => 'Google Analytics measurement ID',
            'plausible_domain' => 'Plausible domain',
            'google_site_verification' => 'Google Search Console verification code',
            'bing_site_verification' => 'Bing Webmaster verification code',
        ],
        'seo' => [
            'title_separator' => 'Title separator',
            'default_meta_description' => 'Default meta description',
            'default_og_image' => 'Default social sharing image',
            'canonical_host' => 'Canonical host',
            'www_policy' => 'www policy',
            'force_https' => 'Redirect to https',
            'trailing_slash' => 'Trailing slash policy',
            'robots_extra' => 'Extra robots.txt rules',
            'indexnow_enabled' => 'Ping IndexNow on publish',
        ],
        'geo' => [
            'llms_intro' => 'llms.txt introduction',
            'ai_preset' => 'AI crawler policy',
            'ai_bots' => 'Allowed AI crawlers',
        ],
        'privacy' => [
            'consent_enabled' => 'Show cookie consent banner',
            'access_log_retention_days' => 'Keep access log for (days)',
        ],
        'features' => [
            'access_log' => 'Access log and built-in page counter',
            'page_cache' => 'Full-page cache for visitors',
            'contact_form' => 'Contact form',
            'newsletter' => 'Newsletter sign-up (double opt-in)',
            'ai_assistant' => 'AI writing assistant (needs a provider key in .env)',
            'pexels' => 'Pexels stock photos (needs PEXELS_API_KEY in .env)',
            'publish_gate' => 'Publish gate (minimum scores before publishing)',
        ],
        'ai' => [
            'monthly_budget_usd' => 'Monthly AI budget (USD)',
        ],
    ],
    'help' => [
        'seo' => [
            'canonical_host' => 'e.g. example.com. Requests on other hosts are redirected (301). Leave empty to disable.',
            'robots_extra' => 'Appended to the generated robots.txt.',
            'indexnow_enabled' => 'Requires INDEXNOW_KEY in .env.',
        ],
        'geo' => [
            'llms_intro' => 'Shown at the top of /llms.txt. Defaults to the tagline.',
            'ai_preset' => '"Search only" lets AI assistants cite you but blocks model-training crawlers.',
            'ai_bots' => 'Checked bots are allowed in robots.txt, the rest are disallowed.',
        ],
        'analytics' => [
            'google_analytics_id' => 'Loaded only after the visitor accepts analytics cookies.',
        ],
        'ai' => [
            'monthly_budget_usd' => 'AI actions are disabled for the rest of the month once the estimated cost reaches this amount. 0 = no limit.',
        ],
        'social' => [
            'links' => 'Network name → profile URL. Used in the footer and as sameAs in structured data.',
        ],
    ],
    'options' => [
        'organization' => 'An organization',
        'person' => 'A person',
        'ignore' => 'Leave as is',
        'www' => 'Always www',
        'non_www' => 'Never www',
        'remove' => 'Remove trailing slash',
        'add' => 'Add trailing slash',
        'allow_all' => 'Allow all AI crawlers',
        'search_only' => 'Allow AI search, block AI training',
        'block_all' => 'Block all AI crawlers',
        'custom' => 'Choose per crawler',
    ],
    'map' => [
        'key' => 'Name',
        'value' => 'URL',
    ],
];
