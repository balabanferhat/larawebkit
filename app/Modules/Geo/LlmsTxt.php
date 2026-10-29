<?php

namespace App\Modules\Geo;

use App\Modules\Content\Models\Page;
use App\Modules\Content\Models\Post;
use App\Modules\Seo\CanonicalUrl;
use App\Modules\Seo\SeoData;
use Illuminate\Support\Facades\Cache;

/**
 * /llms.txt (site summary + curated links per section, https://llmstxt.org)
 * and /llms-full.txt (full text of published, indexable content). Both are
 * cached and rebuilt after any content change.
 */
class LlmsTxt
{
    public const CACHE_KEY = 'larawebkit.llms';

    public function __construct(private MarkdownTwin $markdown) {}

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY.'.index');
        Cache::forget(self::CACHE_KEY.'.full');
    }

    public function index(): string
    {
        return Cache::rememberForever(self::CACHE_KEY.'.index', function () {
            $out = ['# '.SeoData::siteName(), ''];
            $intro = trim((string) (setting('geo.llms_intro') ?: setting('general.tagline') ?: setting('seo.default_meta_description')));
            if ($intro !== '') {
                $out[] = '> '.preg_replace('/\s+/', ' ', $intro);
                $out[] = '';
            }
            $out[] = __('larawebkit-geo.llms.markdown_note', ['example' => CanonicalUrl::for(post_path('example.md', (string) setting('general.default_locale', 'en')))]);
            $out[] = '';

            $pages = Page::indexable()->where('template', '!=', 'legal')->orderBy('sort_order')->orderBy('path')->get();
            if ($pages->isNotEmpty()) {
                $out[] = '## '.__('larawebkit-geo.llms.pages');
                $out[] = '';
                foreach ($pages as $page) {
                    $out[] = $this->link($page);
                }
                $out[] = '';
            }

            $posts = Post::indexable()->where('members_only', false)->latest('published_at')->limit(200)->get();
            if ($posts->isNotEmpty()) {
                $out[] = '## '.__('larawebkit-geo.llms.posts');
                $out[] = '';
                foreach ($posts as $post) {
                    $out[] = $this->link($post);
                }
                $out[] = '';
            }

            $legal = Page::indexable()->where('template', 'legal')->get();
            $out[] = '## Optional';
            $out[] = '';
            $out[] = '- ['.__('larawebkit-geo.llms.full').']('.CanonicalUrl::for('/llms-full.txt').')';
            $out[] = '- [Sitemap]('.CanonicalUrl::for('/sitemap.xml').')';
            foreach ($legal as $page) {
                $out[] = $this->link($page);
            }

            return trim(implode("\n", $out))."\n";
        });
    }

    public function full(): string
    {
        return Cache::rememberForever(self::CACHE_KEY.'.full', function () {
            $parts = ['# '.SeoData::siteName(), ''];
            $limit = (int) config('larawebkit-geo.llms_full_max_items', 500);
            $items = Page::indexable()->with('author')->orderBy('path')->get()
                ->concat(Post::indexable()->where('members_only', false)->with('author')->latest('published_at')->limit($limit)->get());

            foreach ($items as $item) {
                $parts[] = $this->markdown->render($item);
                $parts[] = "\n---\n";
            }

            return trim(implode("\n", $parts))."\n";
        });
    }

    private function link($item): string
    {
        $description = trim($item->summary ?: $item->seoDescription());

        return '- ['.$item->title.']('.CanonicalUrl::for($item->publicPath()).'.md)'.($description !== '' ? ': '.preg_replace('/\s+/', ' ', $description) : '');
    }
}
