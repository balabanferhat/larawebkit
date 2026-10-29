<?php

use App\Modules\Content\Models\Category;
use App\Modules\Content\Models\Page;
use App\Modules\Content\Models\Post;
use App\Modules\Settings\SettingsRepository;

/** Routes are registered at boot: rebuild the app with the given prefix and a fresh database. */
function bootWithPostPrefix(string $prefix, string $blog = 'blog'): void
{
    foreach (['POST_PREFIX' => $prefix, 'BLOG_PATH' => $blog] as $key => $value) {
        putenv("$key=$value");
        $_ENV[$key] = $_SERVER[$key] = $value;
    }
    test()->refreshApplication();
    test()->artisan('migrate');
    app(SettingsRepository::class)->set('features.page_cache', false);
}

afterEach(function () {
    foreach (['POST_PREFIX', 'BLOG_PATH'] as $key) {
        putenv($key);
        unset($_ENV[$key], $_SERVER[$key]);
    }
});

it('serves posts at /{slug} with an empty prefix, pages first', function () {
    bootWithPostPrefix('');
    $post = Post::factory()->published()->create(['title' => 'Root post', 'slug' => 'root-post']);
    $news = Category::create(['name' => 'News', 'slug' => 'news']);
    $post->categories()->attach($news);
    Page::factory()->published()->create(['title' => 'Same slug page', 'slug' => 'clash']);
    Post::factory()->published()->create(['title' => 'Same slug post', 'slug' => 'clash']);

    expect($post->publicPath())->toBe('/root-post')->and($news->publicPath())->toBe('/blog/category/news');
    $this->get('/root-post')->assertOk()->assertSee('Root post');
    $this->get('/root-post.md')->assertOk()->assertSee('# Root post', false);
    $this->get('/clash')->assertOk()->assertSee('Same slug page');
    $this->get('/blog')->assertOk()->assertSee('Root post');
    $this->get('/blog/category/news')->assertOk()->assertSee('Root post');
    $this->get('/blog/root-post')->assertNotFound();
    $this->get('/sitemap-posts.xml')->assertSee(url('/root-post'));
});

it('uses a custom prefix and listing path everywhere', function () {
    bootWithPostPrefix('yazi', 'yazilar');
    $post = Post::factory()->published()->create(['title' => 'Prefixed', 'slug' => 'prefixed']);

    expect($post->publicPath())->toBe('/yazi/prefixed')->and(blog_path())->toBe('/yazilar');
    $this->get('/yazi/prefixed')->assertOk()->assertSee('Prefixed');
    $this->get('/yazilar')->assertOk()->assertSee('Prefixed');
    $this->get('/blog/prefixed')->assertNotFound();
    $this->get('/feed.xml')->assertSee(url('/yazi/prefixed'));
});
