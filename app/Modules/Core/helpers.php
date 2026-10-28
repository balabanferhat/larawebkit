<?php

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
