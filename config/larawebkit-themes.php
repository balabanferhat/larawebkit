<?php

return [

    // Theme folders (each with a theme.json). See docs/recipes/new-theme.md.
    'path' => base_path('themes'),

    // Where activation copies a theme's prebuilt assets, relative to public/.
    'public_path' => 'themes',

    /*
    | Upload themes as .zip in Appearance → Themes. Off by default: a theme
    | contains Blade views, i.e. PHP code that runs on your server, so only
    | enable it if you trust every super-admin and every theme you install.
    | Even when enabled, only super-admins can upload.
    */
    'allow_upload' => (bool) env('THEMES_ALLOW_UPLOAD', false),

    // What `larawebkit:demo` created, so the next run (or --remove) can delete it.
    'demo_manifest' => storage_path('app/larawebkit-demo.json'),

    'max_upload_mb' => (int) env('THEMES_MAX_UPLOAD_MB', 20),

    // File types accepted inside an uploaded theme archive.
    'allowed_extensions' => ['json', 'php', 'css', 'js', 'webp', 'png', 'jpg', 'jpeg', 'svg', 'woff2', 'txt', 'md'],

];
