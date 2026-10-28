<?php

namespace App\Modules\Themes;

use App\Modules\Media\Support\ContentImages;
use Illuminate\Support\Str;

/**
 * Prepares stored editor HTML for the front end: responsive images, heading
 * anchors and a table of contents for long posts.
 */
class ContentRenderer
{
    /** @return array{html: string, toc: list<array{id: string, text: string, level: int}>} */
    public static function prepare(?string $html, int $tocMinHeadings = 3): array
    {
        $html = ContentImages::render($html);
        $toc = [];
        $used = [];

        $html = (string) preg_replace_callback('/<(h[23])([^>]*)>(.*?)<\/\1>/is', function (array $m) use (&$toc, &$used) {
            $text = trim(html_entity_decode(strip_tags($m[3]), ENT_QUOTES | ENT_HTML5));
            if (preg_match('/\bid="([^"]+)"/', $m[2], $existing)) {
                $id = $existing[1];
            } else {
                $base = Str::slug($text) ?: 'section';
                $id = $base;
                $i = 2;
                while (isset($used[$id])) {
                    $id = $base.'-'.$i++;
                }
                $m[2] .= ' id="'.$id.'"';
            }
            $used[$id] = true;
            $toc[] = ['id' => $id, 'text' => $text, 'level' => (int) substr($m[1], 1)];

            return '<'.$m[1].$m[2].'>'.$m[3].'</'.$m[1].'>';
        }, $html);

        $h2 = count(array_filter($toc, fn ($t) => $t['level'] === 2));

        return ['html' => $html, 'toc' => $h2 >= $tocMinHeadings ? $toc : []];
    }

    public static function readingMinutes(int $words): int
    {
        return max(1, (int) round($words / 220));
    }
}
