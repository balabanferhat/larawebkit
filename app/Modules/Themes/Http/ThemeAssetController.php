<?php

namespace App\Modules\Themes\Http;

use App\Modules\Themes\ThemeManager;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Serves a theme's prebuilt assets until they are published to public/themes
 * (activation or `larawebkit:themes:publish` copies them for the web server).
 */
class ThemeAssetController
{
    private const TYPES = [
        'css' => 'text/css', 'js' => 'text/javascript', 'webp' => 'image/webp', 'png' => 'image/png', 'jpg' => 'image/jpeg',
        'svg' => 'image/svg+xml', 'woff2' => 'font/woff2', 'json' => 'application/json',
    ];

    public function __invoke(ThemeManager $themes, string $theme, string $path): BinaryFileResponse
    {
        $found = $themes->get($theme);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        abort_if(! $found || str_contains($path, '..') || ! isset(self::TYPES[$extension]), 404);

        $file = $path === basename((string) $found->screenshot()) ? $found->screenshot() : $found->assetPath($path);
        abort_unless(is_file($file) && str_starts_with((string) realpath($file), realpath($found->path).DIRECTORY_SEPARATOR), 404);

        return response()->file($file, [
            'Content-Type' => self::TYPES[$extension],
            'Cache-Control' => request()->has('v') ? 'public, max-age=31536000, immutable' : 'public, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
