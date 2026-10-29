<?php

if (! function_exists('blog_path')) {
    /** The post listing (config larawebkit-content.blog_path), localized: blog_path() => '/blog', blog_path('/category/x'). */
    function blog_path(string $suffix = '', ?string $locale = null): string
    {
        return localized_path('/'.config('larawebkit-content.blog_path', 'blog').($suffix === '' ? '' : '/'.ltrim($suffix, '/')), $locale);
    }
}

if (! function_exists('post_path')) {
    /** Path of a post: /blog/{slug}, or /{slug} with an empty post_prefix. */
    function post_path(string $slug, ?string $locale = null): string
    {
        $prefix = (string) config('larawebkit-content.post_prefix', 'blog');

        return localized_path(($prefix === '' ? '' : '/'.$prefix).'/'.$slug, $locale);
    }
}

if (! function_exists('localized_path')) {
    /**
     * Prefix a path with the locale unless it is the default locale:
     * localized_path('/blog', 'tr') => '/tr/blog'.
     */
    function localized_path(string $path, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $path = '/'.ltrim($path, '/');
        $default = (string) setting('general.default_locale', config('app.locale', 'en'));

        if ($locale === $default || $locale === '') {
            return $path;
        }

        return '/'.$locale.($path === '/' ? '' : $path);
    }
}
