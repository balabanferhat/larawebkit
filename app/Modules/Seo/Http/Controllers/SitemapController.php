<?php

namespace App\Modules\Seo\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Content\Models\Category;
use App\Modules\Content\Models\Page;
use App\Modules\Content\Models\Post;
use App\Modules\Seo\CanonicalUrl;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/** sitemap.xml index + per-type sitemaps with lastmod and image entries. */
class SitemapController extends Controller
{
    public const CACHE_KEY = 'larawebkit.sitemap';

    public const TYPES = ['pages', 'posts', 'categories'];

    public function index(): Response
    {
        $xml = Cache::rememberForever(self::CACHE_KEY.'.index', function () {
            $out = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
            foreach (self::TYPES as $type) {
                $last = $this->query($type)->max('updated_at');
                if ($last === null) {
                    continue;
                }
                $out .= '  <sitemap><loc>'.e(CanonicalUrl::for("/sitemap-$type.xml")).'</loc><lastmod>'.Carbon::parse($last)->toAtomString().'</lastmod></sitemap>'."\n";
            }

            return $out.'</sitemapindex>';
        });

        return $this->xml($xml);
    }

    public function show(string $type): Response
    {
        abort_unless(in_array($type, self::TYPES, true), 404);

        $xml = Cache::rememberForever(self::CACHE_KEY.".$type", function () use ($type) {
            $out = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
                .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">'."\n";
            $items = $this->query($type)->limit((int) config('larawebkit-seo.sitemap.max_urls', 5000))->get();
            foreach ($items as $item) {
                $out .= '  <url><loc>'.e(CanonicalUrl::for($item->publicPath())).'</loc><lastmod>'.$item->updated_at->toAtomString().'</lastmod>';
                foreach ($this->images($item) as $image) {
                    $out .= '<image:image><image:loc>'.e($image).'</image:loc></image:image>';
                }
                $out .= "</url>\n";
            }

            return $out.'</urlset>';
        });

        return $this->xml($xml);
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY.'.index');
        foreach (self::TYPES as $type) {
            Cache::forget(self::CACHE_KEY.".$type");
        }
    }

    private function query(string $type)
    {
        return match ($type) {
            'pages' => Page::indexable()->with('featuredMedia')->orderBy('path'),
            'posts' => Post::indexable()->with('featuredMedia')->latest('published_at'),
            // Only categories with published posts: no thin, empty archive pages.
            'categories' => Category::whereHas('posts', fn ($q) => $q->indexable())->orderBy('name'),
        };
    }

    /** @return list<string> */
    private function images($item): array
    {
        if (! isset($item->featured_media_id)) {
            return [];
        }
        $urls = [];
        if ($item->featuredMedia) {
            $urls[] = $item->featuredMedia->url(1280);
        }

        return array_values(array_filter($urls));
    }

    private function xml(string $xml): Response
    {
        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
