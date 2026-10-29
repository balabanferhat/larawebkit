<?php

return [
    // Posts live at /{post_prefix}/{slug}. Empty: /{slug}, like WordPress "post name" permalinks (a page
    // with the same path wins). Changing it later moves every post URL: add redirects for the old ones.
    'post_prefix' => trim((string) env('POST_PREFIX', 'blog'), '/'),

    // The post listing, and the base of the category and tag archives (/blog/category/{slug}).
    'blog_path' => trim((string) env('BLOG_PATH', 'blog'), '/') ?: 'blog',
];
