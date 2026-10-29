<?php

namespace App\Modules\Themes\Demo;

use App\Models\User;
use App\Modules\Content\Models\Category;
use App\Modules\Content\Models\Menu;
use App\Modules\Content\Models\Page;
use App\Modules\Content\Models\Post;
use App\Modules\Content\Models\Tag;
use App\Modules\Media\ImagePipeline;
use App\Modules\Media\Models\Media;
use App\Modules\Settings\SettingsRepository;
use App\Modules\Themes\ThemeManager;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * The API a theme's demo.php uses to create its demo site: settings, generated
 * images, authors, categories, posts, pages built from blocks and menus.
 * Everything created is recorded in storage/app/larawebkit-demo.json so the
 * next `larawebkit:demo` run removes it first (re-runs are idempotent).
 */
class DemoBuilder
{
    /** @var array<string, list<int>> */
    private array $created = ['media' => [], 'pages' => [], 'posts' => [], 'categories' => [], 'tags' => [], 'users' => [], 'menus' => []];

    /** @var array<string, Media> */
    private array $images = [];

    /** @var array<string, Category> */
    private array $categories = [];

    /** @var array<string, User> */
    private array $authors = [];

    /** @var array<string, Page> */
    private array $pages = [];

    private int $postIndex = 0;

    public function __construct(
        public readonly string $theme,
        public readonly string $locale,
        private DemoImages $art,
        private ImagePipeline $pipeline,
        private SettingsRepository $settings,
    ) {}

    public static function manifestPath(): string
    {
        return (string) config('larawebkit-themes.demo_manifest', storage_path('app/larawebkit-demo.json'));
    }

    /** Remove everything a previous demo run created. */
    public static function clean(): int
    {
        $path = self::manifestPath();
        if (! is_file($path)) {
            return 0;
        }
        $manifest = (array) json_decode((string) file_get_contents($path), true);
        $count = 0;
        foreach (['posts' => Post::class, 'pages' => Page::class] as $key => $model) {
            foreach ($model::withTrashed()->whereIn('id', (array) ($manifest[$key] ?? []))->orderByDesc('id')->get() as $item) {
                $item->forceDelete();
                $count++;
            }
        }
        foreach (['menus' => Menu::class, 'categories' => Category::class, 'tags' => Tag::class] as $key => $model) {
            $count += $model::whereIn('id', (array) ($manifest[$key] ?? []))->get()->each->delete()->count();
        }
        $count += User::whereIn('id', (array) ($manifest['users'] ?? []))->get()->each->delete()->count();
        foreach (Media::whereIn('id', (array) ($manifest['media'] ?? []))->get() as $media) {
            app(ImagePipeline::class)->deleteFiles($media);
            $media->delete();
            $count++;
        }
        File::delete($path);

        return $count;
    }

    public function save(): void
    {
        File::put(self::manifestPath(), json_encode($this->created, JSON_PRETTY_PRINT));
    }

    /** Translated demo string: the theme's own (theme-{name}::demo.key), else the shared pool. */
    public function t(string $key, array $replace = []): string
    {
        foreach (app(ThemeManager::class)->chain($this->theme) as $theme) {
            $full = "theme-{$theme->name}::demo.$key";
            if (__($full, [], $this->locale) !== $full) {
                return __($full, $replace, $this->locale);
            }
        }

        return __("larawebkit-demo.$key", $replace, $this->locale);
    }

    /** @return array<mixed> */
    public function list(string $key): array
    {
        $value = null;
        foreach (app(ThemeManager::class)->chain($this->theme) as $theme) {
            $full = "theme-{$theme->name}::demo.$key";
            if (is_array($candidate = __($full, [], $this->locale))) {
                $value = $candidate;
                break;
            }
        }
        $value ??= __("larawebkit-demo.$key", [], $this->locale);

        return is_array($value) ? $value : [];
    }

    /** @param  array<string, mixed>  $values */
    public function settings(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->settings->set($key, $value);
        }
    }

    /** @return array{0: string, 1: string} brand and accent of the theme */
    public function palette(): array
    {
        $defaults = app(ThemeManager::class)->defaults($this->theme);

        return [$defaults['primary'], $defaults['accent']];
    }

    /** A generated placeholder image, stored through the WebP pipeline (cached per key). */
    public function image(string $key, string $kind = 'abstract', int $width = 1600, int $height = 1000, ?string $alt = null, ?array $palette = null): Media
    {
        if (isset($this->images[$key])) {
            return $this->images[$key];
        }
        $binary = $this->art->make($kind, $width, $height, $palette ?? $this->palette(), crc32($this->theme.$key));
        $media = $this->pipeline->store($binary, Str::slug($this->theme.'-'.$key).'.jpg', [
            'title' => Str::headline($key),
            'alt' => $alt ?? '',
            'source' => 'demo',
        ]);
        $this->created['media'][] = $media->id;

        return $this->images[$key] = $media;
    }

    public function category(string $key, string $name, string $description = ''): Category
    {
        if (isset($this->categories[$key])) {
            return $this->categories[$key];
        }
        $category = Category::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'description' => $description]);
        if ($category->wasRecentlyCreated) {
            $this->created['categories'][] = $category->id;
        }

        return $this->categories[$key] = $category;
    }

    public function author(string $key, string $name, string $jobTitle, string $bio): User
    {
        if (isset($this->authors[$key])) {
            return $this->authors[$key];
        }
        $user = User::create([
            'name' => $name,
            'email' => 'demo-'.Str::slug($key).'-'.Str::lower(Str::random(6)).'@example.com',
            'password' => Str::password(32),
            'is_active' => true,
            'job_title' => $jobTitle,
            'bio' => $bio,
            'locale' => $this->locale,
            'avatar_media_id' => $this->image("avatar-$key", 'portrait', 480, 480, $name)->id,
        ]);
        if (method_exists($user, 'assignRole')) {
            rescue(fn () => $user->assignRole('author'), report: false);
        }
        $this->created['users'][] = $user->id;

        return $this->authors[$key] = $user;
    }

    /**
     * @param  array<string, mixed>  $attributes  excerpt, category keys, tags, author key, image key/kind, days_ago, content
     */
    public function post(string $title, array $attributes = []): Post
    {
        $index = $this->postIndex++;
        $image = $attributes['image'] ?? null;
        $post = Post::create([
            'title' => $title,
            'slug' => Str::slug($title),
            'locale' => $this->locale,
            'excerpt' => $attributes['excerpt'] ?? $this->t('post_excerpt'),
            'content' => $attributes['content'] ?? $this->article($index),
            'summary' => $attributes['summary'] ?? null,
            'status' => 'published',
            'published_at' => now()->subDays((int) ($attributes['days_ago'] ?? ($index * 3 + 1)))->setTime(9, 30),
            'author_id' => isset($attributes['author']) ? $this->authors[$attributes['author']]->id ?? null : (collect($this->authors)->values()[$index % max(1, count($this->authors))]->id ?? auth()->id()),
            'featured_media_id' => $image === false ? null : $this->image($image ?? 'post-'.$index, $attributes['image_kind'] ?? 'abstract', 1600, 1000, $title, $attributes['palette'] ?? null)->id,
            'meta_description' => $attributes['excerpt'] ?? null,
        ]);
        $post->categories()->sync(array_map(fn ($key) => $this->categories[$key]->id, (array) ($attributes['categories'] ?? [])));
        $tagIds = [];
        foreach ((array) ($attributes['tags'] ?? []) as $tagName) {
            $tag = Tag::firstOrCreate(['slug' => Str::slug($tagName)], ['name' => $tagName]);
            if ($tag->wasRecentlyCreated) {
                $this->created['tags'][] = $tag->id;
            }
            $tagIds[] = $tag->id;
        }
        $post->tags()->sync($tagIds);
        $this->created['posts'][] = $post->id;

        return $post;
    }

    /**
     * @param  list<array{type: string, data: array<string, mixed>}>  $blocks
     * @param  array<string, mixed>  $attributes
     */
    public function page(string $slug, string $title, array $blocks = [], array $attributes = []): Page
    {
        // Never touch the site's own pages: on a clash the demo page gets a "-demo" slug.
        if (Page::withTrashed()->where('locale', $this->locale)->where('slug', $slug)->where('parent_id', $attributes['parent_id'] ?? null)->exists()) {
            $slug .= '-demo';
        }
        $page = Page::create([
            'title' => $title,
            'slug' => $slug,
            'locale' => $this->locale,
            'content' => $attributes['content'] ?? '',
            'blocks' => $blocks ?: null,
            'template' => $attributes['template'] ?? 'default',
            'parent_id' => $attributes['parent_id'] ?? null,
            'sort_order' => $attributes['sort_order'] ?? 0,
            'summary' => $attributes['summary'] ?? null,
            'meta_description' => $attributes['meta_description'] ?? null,
            'status' => 'published',
            'published_at' => now()->subDays(30),
            'author_id' => $attributes['author_id'] ?? auth()->id(),
            'featured_media_id' => $attributes['featured_media_id'] ?? null,
        ]);
        $this->created['pages'][] = $page->id;

        return $this->pages[$slug] = $page;
    }

    public function pageRef(string $slug): ?Page
    {
        return $this->pages[$slug] ?? null;
    }

    /**
     * Replace the menu at a location. Items: [label, url|page slug|null, children[]].
     *
     * @param  list<array{0: string, 1: ?string, 2?: list<array{0: string, 1: string}>}>  $items
     */
    public function menu(string $location, string $name, array $items): Menu
    {
        Menu::where('location', $location)->get()->each->delete();
        $menu = Menu::create(['name' => $name, 'location' => $location]);
        foreach ($items as $i => $item) {
            $parent = $menu->items()->create($this->menuItem($item, $i));
            foreach ((array) ($item[2] ?? []) as $j => $child) {
                $parent->children()->create($this->menuItem($child, $j) + ['menu_id' => $menu->id]);
            }
        }
        $this->created['menus'][] = $menu->id;

        return $menu;
    }

    /** A demo article body (headings, paragraphs, a list and a quote) from the shared text pool. */
    public function article(int $seed = 0): string
    {
        $paragraphs = array_values($this->list('article.paragraphs'));
        $headings = array_values($this->list('article.headings'));
        $list = array_values($this->list('article.list'));
        $p = fn (int $n) => '<p>'.e($paragraphs[($seed + $n) % max(1, count($paragraphs))] ?? '').'</p>';
        $h = fn (int $n) => '<h2>'.e($headings[($seed + $n) % max(1, count($headings))] ?? '').'</h2>';

        return $p(0).$p(1)
            .$h(0).$p(2).$p(3)
            .'<ul>'.implode('', array_map(fn ($item) => '<li>'.e($item).'</li>', $list)).'</ul>'
            .$h(1).$p(4).'<blockquote><p>'.e($this->t('article.quote')).'</p></blockquote>'.$p(5)
            .$h(2).$p(6).$p(7);
    }

    /** @param  array{0: string, 1: ?string}  $item */
    private function menuItem(array $item, int $sort): array
    {
        [$label, $target] = $item;
        $page = $target !== null && ! str_starts_with($target, '/') && ! str_starts_with($target, 'http') && ! str_starts_with($target, '#') ? ($this->pages[$target] ?? null) : null;

        return $page
            ? ['label' => $label, 'linkable_type' => $page->getMorphClass(), 'linkable_id' => $page->id, 'sort_order' => $sort]
            : ['label' => $label, 'url' => $target, 'sort_order' => $sort];
    }
}
