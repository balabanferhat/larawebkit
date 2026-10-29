<?php

/*
|--------------------------------------------------------------------------
| Typed settings editable in the admin (Settings page)
|--------------------------------------------------------------------------
|
| key => [type, default, group, (options), (rules)]
| Types: string, text, bool, int, email, url, select, multiselect, media, map, bots.
| Labels come from lang/{locale}/larawebkit-settings.php ("keys.<key>").
| Secrets (API keys, passwords) never go here: they stay in .env.
| Recipe: docs/recipes/new-setting.md
|
*/

return [

    // General
    'general.site_name' => ['type' => 'string', 'default' => env('APP_NAME', 'larawebkit'), 'group' => 'general', 'rules' => ['required', 'max:120']],
    'general.tagline' => ['type' => 'string', 'default' => '', 'group' => 'general', 'rules' => ['max:200']],
    'general.logo' => ['type' => 'media', 'default' => null, 'group' => 'general'],
    'general.contact_email' => ['type' => 'email', 'default' => '', 'group' => 'general'],
    'general.default_locale' => ['type' => 'select', 'default' => 'en', 'group' => 'general', 'options' => 'locales'],
    'general.active_locales' => ['type' => 'multiselect', 'default' => ['en'], 'group' => 'general', 'options' => 'locales'],
    'general.theme' => ['type' => 'select', 'default' => 'minimal', 'group' => 'general', 'options' => 'themes'],
    'general.posts_per_page' => ['type' => 'int', 'default' => 10, 'group' => 'general', 'rules' => ['integer', 'min:1', 'max:100']],

    // Social links (also used as Organization sameAs)
    'social.links' => ['type' => 'map', 'default' => [], 'group' => 'social'],

    // Organization / Person (JSON-LD, E-E-A-T)
    'organization.type' => ['type' => 'select', 'default' => 'Organization', 'group' => 'organization', 'options' => ['Organization' => 'Organization', 'Person' => 'Person']],
    'organization.name' => ['type' => 'string', 'default' => '', 'group' => 'organization'],
    'organization.logo' => ['type' => 'media', 'default' => null, 'group' => 'organization'],
    'organization.phone' => ['type' => 'string', 'default' => '', 'group' => 'organization'],
    'organization.address' => ['type' => 'text', 'default' => '', 'group' => 'organization'],

    // Analytics & verification (scripts only load after consent)
    'analytics.google_analytics_id' => ['type' => 'string', 'default' => '', 'group' => 'analytics', 'rules' => ['nullable', 'regex:/^(G|UA)-[A-Z0-9-]+$/']],
    'analytics.plausible_domain' => ['type' => 'string', 'default' => '', 'group' => 'analytics'],
    'analytics.google_site_verification' => ['type' => 'string', 'default' => '', 'group' => 'analytics'],
    'analytics.bing_site_verification' => ['type' => 'string', 'default' => '', 'group' => 'analytics'],

    // SEO
    'seo.title_separator' => ['type' => 'string', 'default' => '–', 'group' => 'seo'],
    'seo.default_meta_description' => ['type' => 'text', 'default' => '', 'group' => 'seo'],
    'seo.default_og_image' => ['type' => 'media', 'default' => null, 'group' => 'seo'],
    'seo.canonical_host' => ['type' => 'string', 'default' => '', 'group' => 'seo'],
    'seo.www_policy' => ['type' => 'select', 'default' => 'ignore', 'group' => 'seo', 'options' => ['ignore' => 'ignore', 'www' => 'www', 'non-www' => 'non-www']],
    'seo.force_https' => ['type' => 'bool', 'default' => false, 'group' => 'seo'],
    'seo.trailing_slash' => ['type' => 'select', 'default' => 'remove', 'group' => 'seo', 'options' => ['remove' => 'remove', 'add' => 'add', 'ignore' => 'ignore']],
    'seo.robots_extra' => ['type' => 'text', 'default' => '', 'group' => 'seo'],
    'seo.indexnow_enabled' => ['type' => 'bool', 'default' => false, 'group' => 'seo'],

    // GEO / AI crawlers
    'geo.llms_intro' => ['type' => 'text', 'default' => '', 'group' => 'geo'],
    'geo.ai_preset' => ['type' => 'select', 'default' => 'search_only', 'group' => 'geo', 'options' => ['allow_all' => 'allow_all', 'search_only' => 'search_only', 'block_all' => 'block_all', 'custom' => 'custom']],
    'geo.ai_bots' => ['type' => 'bots', 'default' => [], 'group' => 'geo'],

    // Privacy
    'privacy.consent_enabled' => ['type' => 'bool', 'default' => true, 'group' => 'privacy'],
    'privacy.access_log_retention_days' => ['type' => 'int', 'default' => 90, 'group' => 'privacy', 'rules' => ['integer', 'min:1', 'max:3650']],

    // Feature toggles
    'features.access_log' => ['type' => 'bool', 'default' => true, 'group' => 'features'],
    'features.page_cache' => ['type' => 'bool', 'default' => true, 'group' => 'features'],
    'features.contact_form' => ['type' => 'bool', 'default' => true, 'group' => 'features'],
    'features.newsletter' => ['type' => 'bool', 'default' => false, 'group' => 'features'],
    'features.ai_assistant' => ['type' => 'bool', 'default' => true, 'group' => 'features'],
    'features.pexels' => ['type' => 'bool', 'default' => true, 'group' => 'features'],
    'features.publish_gate' => ['type' => 'bool', 'default' => true, 'group' => 'features'],

    // AI
    'ai.monthly_budget_usd' => ['type' => 'int', 'default' => 20, 'group' => 'ai', 'rules' => ['integer', 'min:0']],

];
