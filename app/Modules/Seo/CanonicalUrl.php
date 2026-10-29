<?php

namespace App\Modules\Seo;

/** Absolute URL on the one canonical host/scheme with the trailing-slash policy applied. */
class CanonicalUrl
{
    public static function for(string $path): string
    {
        $base = rtrim((string) config('app.url'), '/');
        $host = (string) setting('seo.canonical_host', '');
        if ($host !== '') {
            $scheme = setting('seo.force_https', false) ? 'https' : (parse_url($base, PHP_URL_SCHEME) ?: 'https');
            $base = $scheme.'://'.$host;
        } elseif (setting('seo.force_https', false)) {
            $base = preg_replace('#^http://#', 'https://', $base);
        }

        $path = '/'.ltrim($path, '/');
        if ($path !== '/') {
            $path = match (setting('seo.trailing_slash', 'remove')) {
                'add' => rtrim($path, '/').'/',
                'remove' => rtrim($path, '/'),
                default => $path,
            };
        }

        return $path === '/' ? $base.'/' : $base.$path;
    }
}
