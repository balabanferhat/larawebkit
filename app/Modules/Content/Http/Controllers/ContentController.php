<?php

namespace App\Modules\Content\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Content\Models\Category;
use App\Modules\Content\Models\Page;
use App\Modules\Content\Models\Post;
use App\Modules\Content\Models\Tag;
use App\Modules\Core\ModuleRegistry;
use App\Modules\Geo\MarkdownTwin;
use App\Modules\Seo\SeoData;
use App\Modules\Themes\ThemeManager;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Public pages: home, blog, posts, pages (hierarchical), taxonomies, authors,
 * search and the admin-only preview. Views come from the active theme.
 */
class ContentController extends Controller
{
    public function __construct(private ThemeManager $theme) {}

    public function home(Request $request): Response
    {
        $page = Page::published()->where('locale', app()->getLocale())->where('path', 'home')->first();
        if ($page) {
            return $this->renderPage($request, $page, canonicalPath: localized_path('/'));
        }

        $posts = Post::published()->where('locale', app()->getLocale())->with('featuredMedia', 'author')->latest('published_at')->limit(6)->get();
        $seo = SeoData::forHome();

        return response()->view($this->theme->view('home'), compact('posts', 'seo'));
    }

    public function blog(Request $request): Response
    {
        $posts = Post::published()->where('locale', app()->getLocale())->with('featuredMedia', 'author', 'categories')
            ->latest('published_at')->paginate((int) setting('general.posts_per_page', 10));
        $seo = SeoData::forListing(__('larawebkit-front.blog'), localized_path('/blog'), $posts);

        return response()->view($this->theme->view('blog'), ['posts' => $posts, 'seo' => $seo, 'heading' => __('larawebkit-front.blog')]);
    }

    public function category(string $slug): Response
    {
        $category = Category::where('slug', $slug)->firstOrFail();
        $posts = $category->posts()->published()->where('locale', app()->getLocale())->with('featuredMedia', 'author')->latest('published_at')->paginate((int) setting('general.posts_per_page', 10));
        $seo = SeoData::forListing($category->name, $category->publicPath(), $posts, $category->description);

        return response()->view($this->theme->view('blog'), ['posts' => $posts, 'seo' => $seo, 'heading' => $category->name, 'description' => $category->description]);
    }

    public function tag(string $slug): Response
    {
        $tag = Tag::where('slug', $slug)->firstOrFail();
        $posts = $tag->posts()->published()->where('locale', app()->getLocale())->with('featuredMedia', 'author')->latest('published_at')->paginate((int) setting('general.posts_per_page', 10));
        // Tag archives are thin by nature: noindex by default.
        $seo = SeoData::forListing('#'.$tag->name, $tag->publicPath(), $posts)->noindex();

        return response()->view($this->theme->view('blog'), ['posts' => $posts, 'seo' => $seo, 'heading' => '#'.$tag->name]);
    }

    public function author(string $slug): Response
    {
        $author = User::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $posts = Post::published()->where('author_id', $author->id)->where('locale', app()->getLocale())->with('featuredMedia')->latest('published_at')->paginate((int) setting('general.posts_per_page', 10));
        abort_if($posts->total() === 0 && blank($author->bio), 404);
        $seo = SeoData::forAuthor($author);

        return response()->view($this->theme->view('author'), compact('author', 'posts', 'seo'));
    }

    public function search(Request $request): Response
    {
        $q = trim(mb_substr((string) $request->query('q'), 0, 100));
        $posts = $q === '' ? collect() : Post::published()->where('locale', app()->getLocale())
            ->where(fn ($w) => $w->where('title', 'like', "%$q%")->orWhere('content', 'like', "%$q%"))
            ->latest('published_at')->limit(30)->get();
        $seo = SeoData::forListing(__('larawebkit-front.search'), localized_path('/search'))->noindex();

        return response()->view($this->theme->view('search'), compact('posts', 'seo', 'q'));
    }

    public function post(Request $request, string $slug): Response
    {
        [$slug, $markdown] = $this->markdownSuffix($slug);
        $post = Post::published()->where('locale', app()->getLocale())->where('slug', $slug)
            ->with('featuredMedia', 'author.avatar', 'categories', 'tags')->firstOrFail();

        if ($markdown || $this->wantsMarkdown($request)) {
            return $this->markdown($post);
        }

        return $this->renderPost($post);
    }

    public function page(Request $request, string $path): Response
    {
        [$path, $markdown] = $this->markdownSuffix(trim($path, '/'));
        $page = Page::published()->where('locale', app()->getLocale())->where('path', $path)->with('featuredMedia', 'author')->firstOrFail();

        if ($markdown || $this->wantsMarkdown($request)) {
            return $this->markdown($page);
        }

        return $this->renderPage($request, $page);
    }

    /** Admin-only preview of drafts and scheduled items (never cached, always noindex). */
    public function preview(Request $request, string $type, int $id): Response
    {
        abort_unless($request->user()?->canAccessPanel(Filament::getPanel('admin')), 403);
        $model = match ($type) {
            'post' => Post::with('featuredMedia', 'author', 'categories', 'tags')->findOrFail($id),
            'page' => Page::with('featuredMedia', 'author')->findOrFail($id),
            default => abort(404),
        };
        app()->setLocale($model->locale);

        $response = $type === 'post' ? $this->renderPost($model, preview: true) : $this->renderPage($request, $model, preview: true);
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }

    private function renderPost(Post $post, bool $preview = false): Response
    {
        $seo = SeoData::forContent($post);
        $related = $post->related();
        $locked = $post->members_only && ! auth()->check();

        return response()->view($this->theme->view('post'), compact('post', 'seo', 'related', 'locked', 'preview'));
    }

    private function renderPage(Request $request, Page $page, ?string $canonicalPath = null, bool $preview = false): Response
    {
        $seo = SeoData::forContent($page, $canonicalPath);
        $template = $this->theme->view('templates.'.$page->template);
        $children = $page->children()->published()->get();

        return response()->view(view()->exists($template) ? $template : $this->theme->view('page'), compact('page', 'seo', 'children', 'preview'));
    }

    /** @return array{0: string, 1: bool} */
    private function markdownSuffix(string $value): array
    {
        if (str_ends_with($value, '.md') && ModuleRegistry::enabled('geo')) {
            return [substr($value, 0, -3), true];
        }

        return [$value, false];
    }

    private function wantsMarkdown(Request $request): bool
    {
        return ModuleRegistry::enabled('geo') && str_contains((string) $request->header('Accept'), 'text/markdown');
    }

    private function markdown(Model $model): Response
    {
        abort_if($model->noindex, 404);

        return response(app(MarkdownTwin::class)->render($model), 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
            'Link' => '<'.$model->url().'>; rel="canonical"',
            'X-Robots-Tag' => 'noindex',
        ]);
    }
}
