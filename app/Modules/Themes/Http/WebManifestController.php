<?php

namespace App\Modules\Themes\Http;

use App\Modules\Seo\SeoData;
use App\Modules\Themes\Design\DesignTokens;
use App\Modules\Themes\Design\Favicons;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class WebManifestController
{
    public function __invoke(DesignTokens $tokens, Favicons $favicons): JsonResponse
    {
        $name = class_exists(SeoData::class) ? SeoData::siteName() : (string) setting('general.site_name', config('app.name'));
        $vars = $tokens->variables()['light'];
        $icons = [];
        foreach (['icon-192.png' => 192, 'icon-512.png' => 512] as $file => $size) {
            if ($url = $favicons->urls()[$file] ?? null) {
                $icons[] = ['src' => $url, 'sizes' => "{$size}x{$size}", 'type' => 'image/png', 'purpose' => 'any'];
            }
        }

        return response()->json(array_filter([
            'name' => $name,
            'short_name' => Str::limit($name, 12, ''),
            'description' => (string) setting('general.tagline') ?: null,
            'lang' => app()->getLocale(),
            'start_url' => '/',
            'scope' => '/',
            'display' => 'minimal-ui',
            'background_color' => $vars['--bg'],
            'theme_color' => $vars['--brand'],
            'icons' => $icons,
        ]), 200, ['Content-Type' => 'application/manifest+json', 'Cache-Control' => 'public, max-age=3600'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
