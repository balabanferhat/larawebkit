<?php

namespace App\Modules\Import\WordPress;

use App\Models\User;
use App\Modules\Content\Models\Category;
use App\Modules\Content\Models\Page;
use App\Modules\Content\Models\Post;
use App\Modules\Content\Models\Redirect;
use App\Modules\Content\Models\Tag;
use App\Modules\Import\ImportMap;
use App\Modules\Media\ImagePipeline;
use App\Modules\Media\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Imports users, categories, tags, pages and posts from a WordPress source. Re-runs are safe: every
 * item is mapped to its local record (import_map), unchanged items are left alone and changed ones
 * updated. Old URLs that differ from the new ones get a 301 redirect.
 */
final class WordPressImporter
{
    public const KINDS = ['users', 'categories', 'tags', 'pages', 'posts'];

    /** @var array<string, array{created: int, updated: int, unchanged: int, skipped: int}> */
    private array $report = [];

    /** @var list<string> */
    private array $warnings = [];

    private string $key = '';

    private string $site = '';

    /** @var array<string, mixed> */
    private array $options = [];

    /** @var array<int, int> remote id → local id, per kind, for this run */
    private array $ids = ['users' => [], 'categories' => [], 'tags' => [], 'pages' => [], 'posts' => []];

    private int $redirects = 0;

    private int $goneCount = 0;

    /** @var list<string> images imported without alt text, for the report */
    private array $missingAlt = [];

    public function __construct(private MarkupCleaner $cleaner) {}

    /**
     * @param  array{dry_run?: bool, skip_categories?: list<string>, gone?: bool, keep_empty_pages?: bool, locale?: string, author?: ?User, images?: bool, media_hosts?: list<string>}  $options
     * @return array{report: array<string, array<string, int>>, redirects: int, gone: int, warnings: list<string>, missing_alt: list<string>}
     */
    public function run(Source $source, array $options = []): array
    {
        // Paths (redirect targets, localized_path) are built in the content's locale, not the console's.
        $previousLocale = app()->getLocale();
        app()->setLocale((string) ($options['locale'] ?? setting('general.default_locale', 'en')));
        try {
            return $this->import($source, $options);
        } finally {
            app()->setLocale($previousLocale);
        }
    }

    /** @param  array<string, mixed>  $options */
    private function import(Source $source, array $options): array
    {
        $this->site = $source->siteUrl();
        $this->key = Str::limit(preg_replace('/^www\./', '', strtolower((string) parse_url($this->site, PHP_URL_HOST))) ?: 'wordpress', 190, '');
        $this->options = $options + ['dry_run' => false, 'skip_categories' => [], 'gone' => false, 'keep_empty_pages' => false, 'locale' => (string) setting('general.default_locale', 'en'), 'author' => null, 'images' => true, 'media_hosts' => []];
        $this->report = array_fill_keys([...self::KINDS, 'media'], ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'skipped' => 0]);

        $data = [];
        foreach (self::KINDS as $kind) {
            $data[$kind] = $source->all($kind);
        }

        $skip = array_map('strtolower', (array) $this->options['skip_categories']);
        $skipIds = collect($data['categories'])->filter(fn ($c) => in_array(strtolower((string) ($c['slug'] ?? '')), $skip, true))->pluck('id')->map(fn ($id) => (int) $id)->all();

        foreach ($data['users'] as $user) {
            $this->importUser($user);
        }
        foreach ($data['categories'] as $category) {
            if (in_array((int) $category['id'], $skipIds, true)) {
                $this->count('categories', 'skipped');
                $this->gone((string) ($category['link'] ?? ''));

                continue;
            }
            $this->importTerm('categories', $category);
        }
        foreach ($data['tags'] as $tag) {
            $this->importTerm('tags', $tag);
        }
        $parents = collect($data['pages'])->pluck('parent')->map(fn ($id) => (int) $id)->filter()->all();
        foreach ($this->parentsFirst($data['pages']) as $page) {
            // Layout parts many themes keep as pages (slider, header, an empty "Blog" page): no content, no
            // children. They are not real pages; their old address goes to the home page.
            if (! $this->options['keep_empty_pages'] && ! in_array((int) $page['id'], $parents, true) && $this->isEmptyPage($page)) {
                $this->count('pages', 'skipped');
                // An empty WordPress "Blog" page sits where the post listing (or events, search...) lives here:
                // a redirect would hide that route, so the page is only skipped, and an old redirect removed.
                $from = Redirect::normalize((string) ($page['link'] ?? ''));
                if ($this->isSystemRoute($from)) {
                    if (! $this->dry()) {
                        Redirect::where('from_path', $from)->where('is_automatic', true)->delete();
                    }
                } else {
                    $this->redirect((string) ($page['link'] ?? ''), localized_path('/'));
                }

                continue;
            }
            $this->importContent('pages', $page, $source);
        }
        foreach ($data['posts'] as $post) {
            if (array_intersect(array_map('intval', (array) ($post['categories'] ?? [])), $skipIds) !== []) {
                $this->count('posts', 'skipped');
                $this->gone((string) ($post['link'] ?? ''));

                continue;
            }
            $this->importContent('posts', $post, $source);
        }

        return ['report' => $this->report, 'redirects' => $this->redirects, 'gone' => $this->goneCount, 'warnings' => $this->warnings, 'missing_alt' => $this->missingAlt];
    }

    /** Whether a route other than the page catch-all answers this path (blog listing, events, search...). */
    private function isSystemRoute(string $path): bool
    {
        if ($path === '/') {
            return true;
        }
        try {
            $route = app('router')->getRoutes()->match(Request::create($path, 'GET'));
        } catch (Throwable) {
            return false;
        }

        return ! in_array($route->getName(), ['front.page', 'front.localized.page'], true);
    }

    /** @param  array<string, mixed>  $page */
    private function isEmptyPage(array $page): bool
    {
        $html = $this->cleaner->clean(Arr::get($page, 'content.rendered'), $this->site);

        return (int) ($page['featured_media'] ?? 0) === 0 && trim(MarkupCleaner::text($html)) === '' && ! str_contains($html, '<img');
    }

    /**
     * --gone: the old URL of something left out on purpose answers 410 Gone instead of 404, so search
     * engines drop it. firstOrCreate: an admin who later turns it into a 301 keeps that on re-runs.
     */
    private function gone(string $oldUrl): void
    {
        if (! $this->options['gone'] || $this->dry() || $oldUrl === '' || ! MarkupCleaner::sameSite($oldUrl, $this->site)) {
            return;
        }
        $from = Redirect::normalize($oldUrl);
        if ($from === '/') {
            return;
        }
        if (Redirect::firstOrCreate(['from_path' => $from], ['to_url' => null, 'status_code' => 410, 'is_automatic' => true])->wasRecentlyCreated) {
            $this->goneCount++;
        }
    }

    /** @param  array<string, mixed>  $remote */
    private function importUser(array $remote): void
    {
        $id = (int) $remote['id'];
        if ($this->options['author']) {
            $this->ids['users'][$id] = $this->options['author']->id;
            $this->count('users', 'skipped');

            return;
        }
        $mapped = ImportMap::find($this->key, 'user', $id);
        $slug = Str::slug((string) ($remote['slug'] ?? '')) ?: 'author-'.$id;
        $user = $mapped ? User::find($mapped['local_id']) : User::where('slug', $slug)->first();
        if ($user) {
            $this->ids['users'][$id] = $user->id;
            $this->count('users', $mapped ? 'unchanged' : 'skipped');
            if (! $mapped && ! $this->dry()) {
                ImportMap::put($this->key, 'user', $id, $user->id);
            }

            return;
        }
        $this->count('users', 'created');
        if ($this->dry()) {
            return;
        }
        // An author profile for the byline and author page; no role, so it cannot log in to the admin.
        $user = User::create([
            'name' => html_entity_decode((string) ($remote['name'] ?? $slug), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'slug' => $slug,
            'email' => 'wp-'.$id.'-'.$slug.'@'.$this->key.'.invalid',
            'password' => Hash::make(Str::random(40)),
            'bio' => MarkupCleaner::text($remote['description'] ?? '') ?: null,
            'same_as' => filled($remote['url'] ?? null) ? [(string) $remote['url']] : null,
        ]);
        $this->ids['users'][$id] = $user->id;
        ImportMap::put($this->key, 'user', $id, $user->id);
    }

    /**
     * @param  'categories'|'tags'  $kind
     * @param  array<string, mixed>  $remote
     */
    private function importTerm(string $kind, array $remote): void
    {
        $id = (int) $remote['id'];
        $model = $kind === 'categories' ? Category::class : Tag::class;
        $singular = $kind === 'categories' ? 'category' : 'tag';
        $slug = Str::slug(urldecode((string) ($remote['slug'] ?? ''))) ?: $singular.'-'.$id;
        $attributes = ['name' => html_entity_decode((string) ($remote['name'] ?? $slug), ENT_QUOTES | ENT_HTML5, 'UTF-8')];
        if ($kind === 'categories') {
            $attributes['description'] = MarkupCleaner::text($remote['description'] ?? '') ?: null;
        }

        $mapped = ImportMap::find($this->key, $singular, $id);
        $term = $mapped ? $model::find($mapped['local_id']) : $model::where('slug', $slug)->first();
        $this->count($kind, $term ? ($mapped ? 'unchanged' : 'updated') : 'created');
        if ($this->dry()) {
            return;
        }
        $term ??= new $model(['slug' => $slug]);
        if (! $mapped || ! $term->exists) {
            $term->fill($attributes)->save();
        }
        $this->ids[$kind][$id] = $term->id;
        ImportMap::put($this->key, $singular, $id, $term->id);
        $this->redirect((string) ($remote['link'] ?? ''), $term->publicPath());
    }

    /**
     * @param  'pages'|'posts'  $kind
     * @param  array<string, mixed>  $remote
     */
    private function importContent(string $kind, array $remote, Source $source): void
    {
        $id = (int) $remote['id'];
        $singular = $kind === 'posts' ? 'post' : 'page';
        $modified = (string) ($remote['modified_gmt'] ?? $remote['modified'] ?? '');
        $mapped = ImportMap::find($this->key, $singular, $id);
        $model = $kind === 'posts' ? Post::class : Page::class;
        $local = $mapped ? $model::withTrashed()->find($mapped['local_id']) : null;

        if ($local?->trashed()) {
            $this->count($kind, 'skipped'); // deleted here on purpose: a re-run does not bring it back

            return;
        }
        if ($local && $mapped['remote_modified'] === $modified) {
            $this->ids[$kind][$id] = $local->id;
            $this->count($kind, 'unchanged');
            // Unchanged in WordPress, but the address here may have moved (POST_PREFIX, BLOG_PATH): keep our redirect in step.
            $this->redirect((string) ($remote['link'] ?? ''), $local->publicPath());

            return;
        }
        $this->count($kind, $local ? 'updated' : 'created');
        if ($this->dry()) {
            return;
        }

        $status = match ($remote['status'] ?? 'publish') {
            'publish', 'future' => 'published',
            default => 'draft',
        };
        $date = filled($remote['date_gmt'] ?? null) ? Carbon::parse($remote['date_gmt'], 'UTC') : (filled($remote['date'] ?? null) ? Carbon::parse($remote['date']) : now());
        $content = $this->cleaner->clean(Arr::get($remote, 'content.rendered'), $this->site, fn (string $src, ?int $wpId, string $alt) => $this->inlineImage($source, $src, $wpId, $alt));
        $authorId = $this->ids['users'][(int) ($remote['author'] ?? 0)] ?? $this->options['author']?->id;
        $title = html_entity_decode(strip_tags((string) Arr::get($remote, 'title.rendered', '')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $featured = (int) ($remote['featured_media'] ?? 0) > 0 ? $this->featuredImage($source, (int) $remote['featured_media'], $title) : null;

        $local ??= new $model;
        $local->fill([
            'title' => $title ?: Str::headline((string) ($remote['slug'] ?? $singular)),
            'slug' => $local->exists ? $local->slug : (Str::slug(urldecode((string) ($remote['slug'] ?? ''))) ?: null),
            'locale' => $local->exists ? $local->locale : $this->options['locale'],
            'content' => $content,
            'status' => $status,
            'published_at' => $date,
            'author_id' => $authorId,
            'featured_media_id' => $featured?->id ?? $local->featured_media_id,
        ]);
        if ($kind === 'posts') {
            $local->excerpt = $this->excerpt((string) Arr::get($remote, 'excerpt.rendered'), $content);
        } else {
            $local->parent_id = $this->ids['pages'][(int) ($remote['parent'] ?? 0)] ?? null;
            $local->sort_order = (int) ($remote['menu_order'] ?? 0);
        }
        // Imported items are not "new" for search engines: no IndexNow ping for the whole archive.
        $local->forceFill(['announced_at' => $local->announced_at ?? now()]);
        $local->save();

        if ($kind === 'posts') {
            $local->categories()->sync(array_values(array_filter(array_map(fn ($c) => $this->ids['categories'][(int) $c] ?? null, (array) ($remote['categories'] ?? [])))));
            $local->tags()->sync(array_values(array_filter(array_map(fn ($t) => $this->ids['tags'][(int) $t] ?? null, (array) ($remote['tags'] ?? [])))));
        }
        $this->ids[$kind][$id] = $local->id;
        ImportMap::put($this->key, $singular, $id, $local->id, $modified);
        $this->redirect((string) ($remote['link'] ?? ''), $local->publicPath());
    }

    /**
     * WordPress builds automatic excerpts by stripping the tags of the body, which glues paragraphs together
     * ("Nerede?Dışarıda…"). A hand-written excerpt is kept; an automatic or empty one is built from the body.
     */
    private function excerpt(string $excerpt, string $content): ?string
    {
        $text = MarkupCleaner::text($excerpt);
        $body = MarkupCleaner::text($content);
        $squash = fn (string $s) => preg_replace('/[\s\x{00A0}]+/u', '', rtrim($s, '.…'));
        // Empty, or WordPress's automatic one: built from the body.
        if ($body !== '' && ($text === '' || str_starts_with($squash($body), $squash($text)))) {
            // Paragraph text only: captions, headings and embed links make a poor summary.
            preg_match_all('#<p>(.*?)</p>#s', $content, $m);
            $paragraphs = array_filter(array_map(fn ($p) => MarkupCleaner::text($p), $m[1]), fn ($p) => $p !== '' && ! preg_match('#^https?://\S+$#', $p));
            $text = Str::words(implode(' ', $paragraphs) ?: $body, 55, '');
        }

        return Str::limit($text, 300, '…') ?: null;
    }

    private function featuredImage(Source $source, int $wpId, string $fallbackAlt = ''): ?Media
    {
        $item = $source->media($wpId);
        if (! $item || blank($item['source_url'] ?? null)) {
            $this->warnings[] = "Featured image $wpId not found.";

            return null;
        }

        // Empty alt text is common in WordPress: fall back to a real media title, then the post title.
        $title = MarkupCleaner::text(Arr::get($item, 'title.rendered'));
        $alt = trim((string) ($item['alt_text'] ?? ''));
        if ($alt === '') {
            $alt = self::meaningfulTitle($title) ? $title : $fallbackAlt;
            $this->missingAlt[] = (string) $item['source_url'].' (alt set to "'.$alt.'", please check)';
        }

        return $this->download((string) $item['source_url'], $alt, MarkupCleaner::text(Arr::get($item, 'caption.rendered')), $title);
    }

    /** "IMG_2034", "dsc-0042" or "photo-1" say nothing about the picture. */
    private static function meaningfulTitle(string $title): bool
    {
        return $title !== '' && ! preg_match('/^(img|dsc|dscn|pxl|photo|image|screenshot|whatsapp)?[\s_-]*[\d\s_-]+$/i', $title)
            && ! preg_match('/^(img|dsc|dscn|pxl)[\s_-]?\d+/i', $title);
    }

    /** @return array{src: string, id: int}|null */
    private function inlineImage(Source $source, string $src, ?int $wpId, string $alt): ?array
    {
        if (trim($alt) === '') {
            $this->missingAlt[] = $src;
        }
        $url = $wpId ? ($source->media($wpId)['source_url'] ?? null) : null;
        // Without the attachment, ask for the full size instead of a "-1024x683" thumbnail.
        $url ??= preg_replace('/-\d+x\d+(\.[a-z]{3,4})(\?.*)?$/i', '$1', $src);
        $media = $this->download((string) $url, $alt) ?? ($url !== $src ? $this->download($src, $alt) : null);

        return $media ? ['src' => $media->url(1280), 'id' => $media->id] : null;
    }

    private function download(string $url, string $alt = '', string $caption = '', string $title = ''): ?Media
    {
        if (! $this->options['images'] || ! preg_match('#^https?://#i', $url) || ! $this->allowedHost($url)) {
            return null;
        }
        $key = sha1($url);
        if ($mapped = ImportMap::find($this->key, 'media', $key)) {
            if ($media = Media::find($mapped['local_id'])) {
                $this->count('media', 'unchanged');

                return $media;
            }
        }
        try {
            $response = Http::timeout(60)->get($url);
            if (! $response->successful()) {
                $this->warnings[] = "Image $url: HTTP ".$response->status();

                return null;
            }
            $media = app(ImagePipeline::class)->store($response->body(), basename((string) parse_url($url, PHP_URL_PATH)) ?: 'image.jpg', array_filter([
                'title' => $title ?: null, 'alt' => $alt ?: null, 'caption' => $caption ?: null, 'source' => 'wordpress', 'source_url' => Str::limit($url, 500, ''),
            ]));
        } catch (Throwable $e) {
            $this->warnings[] = "Image $url: ".$e->getMessage();

            return null;
        }
        ImportMap::put($this->key, 'media', $key, $media->id);
        $this->count('media', 'created');

        return $media;
    }

    private function allowedHost(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $site = $this->key;
        foreach ([$site, ...array_map('strtolower', (array) $this->options['media_hosts'])] as $allowed) {
            if ($host === $allowed || str_ends_with($host, '.'.$allowed)) {
                return true;
            }
        }

        return false;
    }

    /** A 301 from the old WordPress path when it differs from the new one. */
    private function redirect(string $oldUrl, string $newPath): void
    {
        if ($this->dry() || $oldUrl === '' || ! MarkupCleaner::sameSite($oldUrl, $this->site)) {
            return;
        }
        $from = Redirect::normalize($oldUrl);
        if ($from === '/') {
            return;
        }
        $existing = Redirect::where('from_path', $from)->first();
        if ($existing && ! $existing->is_automatic) {
            return; // hand-made in the admin: never touched
        }
        if ($from === Redirect::normalize($newPath)) {
            // The item lives at its old address now (e.g. POST_PREFIX emptied): our earlier redirect would hide it.
            $existing?->delete();

            return;
        }
        if ($existing?->to_url === $newPath && $existing->status_code === 301) {
            return; // already in step
        }
        Redirect::updateOrCreate(['from_path' => $from], ['to_url' => $newPath, 'status_code' => 301, 'is_automatic' => true]);
        $this->redirects++;
    }

    /**
     * @param  list<array<string, mixed>>  $pages
     * @return list<array<string, mixed>>
     */
    private function parentsFirst(array $pages): array
    {
        $byId = collect($pages)->keyBy(fn ($p) => (int) $p['id']);
        $depth = function (array $page, int $guard = 0) use (&$depth, $byId): int {
            $parent = (int) ($page['parent'] ?? 0);

            return $parent && $byId->has($parent) && $guard < 20 ? 1 + $depth($byId[$parent], $guard + 1) : 0;
        };

        return collect($pages)->sortBy(fn ($p) => [$depth($p), (int) $p['id']])->values()->all();
    }

    private function count(string $kind, string $what): void
    {
        $this->report[$kind][$what]++;
    }

    private function dry(): bool
    {
        return (bool) $this->options['dry_run'];
    }
}
