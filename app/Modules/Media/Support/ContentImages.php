<?php

namespace App\Modules\Media\Support;

use App\Modules\Media\Models\Media;

/**
 * Editor HTML stores images as <img data-id="{media id}" src="..." alt="...">.
 * At render time they are rewritten into responsive, lazy, dimensioned tags
 * (and Pexels attribution is appended as a caption), so stored content stays
 * valid when variants are regenerated.
 */
class ContentImages
{
    /** @return list<int> */
    public static function ids(?string $html): array
    {
        preg_match_all('/<img\b[^>]*\bdata-id="(\d+)"/i', (string) $html, $m);

        return array_values(array_unique(array_map('intval', $m[1])));
    }

    public static function render(?string $html, ?string $sizes = null): string
    {
        $html = (string) $html;
        $ids = self::ids($html);
        if ($ids === []) {
            return $html;
        }
        $media = Media::whereIn('id', $ids)->get()->keyBy('id');

        return (string) preg_replace_callback('/<img\b([^>]*)>/i', function (array $match) use ($media, $sizes) {
            if (! preg_match('/\bdata-id="(\d+)"/i', $match[1], $id) || ! $media->has((int) $id[1])) {
                return self::lazy($match[0]);
            }
            /** @var Media $item */
            $item = $media[(int) $id[1]];
            preg_match('/\balt="([^"]*)"/i', $match[1], $alt);
            preg_match('/\bwidth="(\d+)/i', $match[1], $width);
            $size = isset($width[1]) ? (int) $width[1] : 'max';
            $tag = ResponsiveImage::tag($item, isset($alt[1]) ? html_entity_decode($alt[1], ENT_QUOTES | ENT_HTML5) : null, $sizes, false, [], $size);
            $credit = ResponsiveImage::attribution($item);

            return $credit ? '<span class="lwk-figure">'.$tag.'<small class="lwk-credit">'.$credit.'</small></span>' : (string) $tag;
        }, $html);
    }

    /** External or legacy images: still lazy-load them. */
    private static function lazy(string $tag): string
    {
        return str_contains($tag, 'loading=') ? $tag : preg_replace('/^<img\b/i', '<img loading="lazy" decoding="async"', $tag);
    }
}
