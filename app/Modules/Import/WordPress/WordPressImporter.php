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

    public function __construct(private MarkupCleaner $cleaner) {}

    /**
     * @param  array{dry_run?: bool, skip_categories?: list<string>, locale?: string, author?: ?User, images?: bool, media_hosts?: list<string>}  $options
     * @return array{report: array<string, array<string, int>>, redirects: int, warnings: list<string>}
     */
    public function run(Source $source, array $options = []): array
    {
        $this->site = $source->siteUrl();
        $this->key = Str::limit(preg_replace('/^www\./', '', strtolower((string) parse_url($this->site, PHP_URL_HOST))) ?: 'wordpress', 190, '');
        $this->options = $options + ['dry_run' => false, 'skip_categories' => [], 'locale' => (string) setting('general.default_locale', 'en'), 'author' => null, 'images' => true, 'media_hosts' => []];
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

                continue;
            }
            $this->importTerm('categories', $category);
        }
        foreach ($data['tags'] as $tag) {
            $this->importTerm('tags', $tag);
        }
        foreach ($this->parentsFirst($data['pages']) as $page) {
            $this->importContent('pages', $page, $source);
        }
        foreach ($data['posts'] as $post) {
            if (array_intersect(array_map('intval', (array) ($post['categories'] ?? [])), $skipIds) !== []) {
                $this->count('posts', 'skipped');

                continue;
            }
            $this->importContent('posts', $post, $source);
        }

        return ['report' => $this->report, 'redirects' => $this->redirects, 'warnings' => $this->warnings];
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
        $featured = (int) ($remote['featured_media'] ?? 0) > 0 ? $this->featuredImage($source, (int) $remote['featured_media']) : null;

        $local ??= new $model;
        $local->fill([
            'title' => html_entity_decode(strip_tags((string) Arr::get($remote, 'title.rendered', '')), ENT_QUOTES | ENT_HTML5, 'UTF-8') ?: Str::headline((string) ($remote['slug'] ?? $singular)),
            'slug' => $local->exists ? $local->slug : (Str::slug(urldecode((string) ($remote['slug'] ?? ''))) ?: null),
            'locale' => $local->exists ? $local->locale : $this->options['locale'],
            'content' => $content,
            'status' => $status,
            'published_at' => $date,
            'author_id' => $authorId,
            'featured_media_id' => $featured?->id ?? $local->featured_media_id,
        ]);
        if ($kind === 'posts') {
            $local->excerpt = Str::limit(MarkupCleaner::text(Arr::get($remote, 'excerpt.rendered')), 300, '…') ?: null;
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

    private function featuredImage(Source $source, int $wpId): ?Media
    {
        $item = $source->media($wpId);
        if (! $item || blank($item['source_url'] ?? null)) {
            $this->warnings[] = "Featured image $wpId not found.";

            return null;
        }

        return $this->download((string) $item['source_url'], (string) ($item['alt_text'] ?? ''), MarkupCleaner::text(Arr::get($item, 'caption.rendered')), MarkupCleaner::text(Arr::get($item, 'title.rendered')));
    }

    /** @return array{src: string, id: int}|null */
    private function inlineImage(Source $source, string $src, ?int $wpId, string $alt): ?array
    {
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
        if ($oldUrl === '' || ! MarkupCleaner::sameSite($oldUrl, $this->site)) {
            return;
        }
        $from = Redirect::normalize($oldUrl);
        if ($from === '/' || $from === Redirect::normalize($newPath)) {
            return;
        }
        $existing = Redirect::where('from_path', $from)->first();
        if ($existing?->to_url === $newPath) {
            return;
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
