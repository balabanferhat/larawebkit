<?php

namespace App\Modules\Media\Support;

use App\Modules\Media\Models\Media;
use Illuminate\Support\HtmlString;

/**
 * Builds <img> tags with srcset/sizes, explicit width/height (no layout shift)
 * and lazy loading - or fetchpriority="high" for the LCP / hero image.
 */
class ResponsiveImage
{
    /** @param  array<string, string|null>  $attributes */
    public static function tag(Media $media, ?string $alt = null, ?string $sizes = null, bool $priority = false, array $attributes = [], string|int $size = 'max'): HtmlString
    {
        $variant = $media->variant($size);
        if (! $variant) {
            return new HtmlString('');
        }

        $attrs = [
            'src' => $media->url($size),
            'srcset' => $size === 'thumb' ? null : $media->srcset(),
            'sizes' => $size === 'thumb' ? null : ($sizes ?? config('larawebkit-media.default_sizes_attribute')),
            'width' => (string) $variant['width'],
            'height' => (string) $variant['height'],
            'alt' => $alt ?? (string) $media->alt,
            'loading' => $priority ? null : 'lazy',
            'decoding' => $priority ? null : 'async',
            'fetchpriority' => $priority ? 'high' : null,
            ...$attributes,
        ];

        $html = '<img';
        foreach ($attrs as $name => $value) {
            if ($value !== null && ($value !== '' || $name === 'alt')) {
                $html .= ' '.$name.'="'.e($value).'"';
            }
        }

        return new HtmlString($html.'>');
    }

    /** <link rel="preload"> for the hero image, placed in <head>. */
    public static function preload(Media $media, ?string $sizes = null): HtmlString
    {
        $url = $media->url();
        if (! $url) {
            return new HtmlString('');
        }

        return new HtmlString('<link rel="preload" as="image" href="'.e($url).'" imagesrcset="'.e($media->srcset()).'" imagesizes="'.e($sizes ?? config('larawebkit-media.default_sizes_attribute')).'" fetchpriority="high">');
    }

    public static function attribution(Media $media): ?HtmlString
    {
        if (! $media->isStock() || ! $media->photographer) {
            return null;
        }

        return new HtmlString(__('larawebkit-media.attribution', [
            'photographer' => '<a href="'.e($media->photographer_url ?: $media->source_url).'" rel="nofollow noopener" target="_blank">'.e($media->photographer).'</a>',
            'source' => '<a href="'.e($media->source_url ?: 'https://www.pexels.com').'" rel="nofollow noopener" target="_blank">Pexels</a>',
        ]));
    }
}
