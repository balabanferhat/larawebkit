<?php

use App\Models\User;
use App\Modules\Content\Models\Category;
use App\Modules\Content\Models\Post;
use App\Modules\Content\Models\Redirect;
use App\Modules\Content\Models\Tag;
use App\Modules\Media\ImagePipeline;
use App\Modules\Seo\Models\NotFoundLog;
use App\Modules\Settings\SettingsRepository;

function publishedPost(array $attributes = []): Post
{
    $author = User::factory()->create(['name' => 'Ada Writer', 'bio' => 'Writes about the web.', 'job_title' => 'Editor', 'same_as' => ['https://github.com/ada']]);

    return Post::factory()->published()->create($attributes + [
        'title' => 'Hello SEO world',
        'slug' => 'hello-seo',
        'meta_description' => 'A friendly description of the hello world article.',
        'author_id' => $author->id,
        'summary' => 'Short answer first.',
        'faq' => [['question' => 'Is it fast?', 'answer' => 'Very.']],
        'sources' => "MDN https://developer.mozilla.org\nW3C",
        'content' => '<h2>Why?</h2><p>Because.</p><h2>How?</h2><p>Like this.</p><h2>When?</h2><p>Now.</p>',
    ]);
}

it('renders a post with full SEO tags and JSON-LD', function () {
    $media = app(ImagePipeline::class)->store(fakeJpeg(1300, 700), 'hero.jpg', ['alt' => 'Hero alt']);
    $post = publishedPost(['featured_media_id' => $media->id]);
    $post->categories()->attach(Category::create(['name' => 'Guides']));

    $html = $this->get('/blog/hello-seo')->assertOk()
        ->assertSee('<title>Hello SEO world – ', false)
        ->assertSee('<meta name="description" content="A friendly description of the hello world article.">', false)
        ->assertSee('<link rel="canonical" href="'.url('/blog/hello-seo').'">', false)
        ->assertSee('property="og:type" content="article"', false)
        ->assertSee('property="og:image"', false)
        ->assertSee('name="twitter:card" content="summary_large_image"', false)
        ->assertSee('rel="preload" as="image"', false)
        ->assertSee('fetchpriority="high"', false)
        ->assertSee('<h1>Hello SEO world</h1>', false)
        ->assertSee('class="lwk-toc"', false)
        ->assertSee('id="why"', false)
        ->assertSee('Short answer first.')
        ->assertSee('rel="author"', false)
        ->getContent();

    expect(substr_count($html, '<h1'))->toBe(1);
    preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
    $graph = collect(json_decode($m[1], true)['@graph'])->keyBy('@type');
    expect($graph->keys()->all())->toContain('Organization', 'WebSite', 'BlogPosting', 'FAQPage', 'BreadcrumbList')
        ->and($graph['BlogPosting']['author']['name'])->toBe('Ada Writer')
        ->and($graph['BlogPosting']['author']['sameAs'])->toBe(['https://github.com/ada'])
        ->and($graph['BlogPosting']['datePublished'])->not->toBeEmpty()
        ->and($graph['BlogPosting']['articleSection'])->toBe('Guides')
        ->and($graph['FAQPage']['mainEntity'][0]['name'])->toBe('Is it fast?')
        ->and($graph['WebSite']['potentialAction']['@type'])->toBe('SearchAction');

    // The OG image is a 1200x630 crop.
    preg_match('#property="og:image" content="([^"]+)"#', $html, $og);
    expect($og[1])->toContain('og-1200x630.webp');
});

it('hides drafts and scheduled posts, and shows an admin preview', function () {
    $draft = Post::factory()->create(['slug' => 'draft-post']);
    Post::factory()->scheduled()->create(['slug' => 'future-post']);

    $this->get('/blog/draft-post')->assertNotFound();
    $this->get('/blog/future-post')->assertNotFound();

    $this->actingAs(editor())->get(route('larawebkit.preview', ['type' => 'post', 'id' => $draft->id]))
        ->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});

it('serves markdown twins at .md and for Accept: text/markdown', function () {
    publishedPost();

    $this->get('/blog/hello-seo.md')->assertOk()
        ->assertHeader('Content-Type', 'text/markdown; charset=UTF-8')
        ->assertSee('# Hello SEO world')
        ->assertSee('## Why?')
        ->assertSee('> **TL;DR:** Short answer first.', false)
        ->assertSee('### Is it fast?')
        ->assertSee('- MDN https://developer.mozilla.org');

    $this->withHeaders(['Accept' => 'text/markdown'])->get('/blog/hello-seo')->assertOk()->assertSee('# Hello SEO world');
});

it('lists content in sitemaps with lastmod and images, skipping noindex', function () {
    $media = app(ImagePipeline::class)->store(fakeJpeg(800, 500), 'pic.jpg');
    publishedPost(['featured_media_id' => $media->id]);
    Post::factory()->published()->create(['slug' => 'hidden', 'noindex' => true]);

    $this->get('/sitemap.xml')->assertOk()->assertSee('sitemap-posts.xml')->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
    $this->get('/sitemap-posts.xml')->assertOk()
        ->assertSee('<loc>'.url('/blog/hello-seo').'</loc>', false)
        ->assertSee('<lastmod>', false)
        ->assertSee('<image:loc>', false)
        ->assertDontSee('/blog/hidden');
});

it('writes the AI crawler policy into robots.txt', function () {
    config(['larawebkit-seo.block_non_production' => false]);

    $this->get('/robots.txt')->assertOk()
        ->assertSee("User-agent: GPTBot\nDisallow: /", false)
        ->assertSee("User-agent: OAI-SearchBot\nAllow: /", false)
        ->assertSee('Disallow: /admin')
        ->assertSee('Sitemap: '.url('/sitemap.xml'));

    app(SettingsRepository::class)->set('geo.ai_preset', 'custom');
    app(SettingsRepository::class)->set('geo.ai_bots', ['GPTBot']);
    $this->get('/robots.txt')->assertSee("User-agent: GPTBot\nAllow: /", false)->assertSee("User-agent: ClaudeBot\nDisallow: /", false);

    app(SettingsRepository::class)->set('seo.robots_extra', 'Disallow: /private');
    $this->get('/robots.txt')->assertSee('Disallow: /private');
});

it('blocks all crawling outside production', function () {
    $this->get('/robots.txt')->assertSee("User-agent: *\nDisallow: /", false);
});

it('generates llms.txt and llms-full.txt and refreshes them on publish', function () {
    $this->get('/llms.txt')->assertOk()->assertDontSee('hello-seo');

    publishedPost();
    app(SettingsRepository::class)->set('geo.llms_intro', 'We write about speed.');

    $this->get('/llms.txt')->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertSee('> We write about speed.', false)
        ->assertSee('- [Hello SEO world]('.url('/blog/hello-seo').'.md): Short answer first.', false)
        ->assertSee('llms-full.txt');
    $this->get('/llms-full.txt')->assertOk()->assertSee('# Hello SEO world')->assertSee('Because.');
});

it('publishes an RSS feed', function () {
    publishedPost();

    $this->get('/feed.xml')->assertOk()->assertSee('<title>Hello SEO world</title>', false)->assertSee('<content:encoded>', false);
});

it('marks tag and paginated archives as noindex', function () {
    $post = publishedPost();
    $post->tags()->attach(Tag::create(['name' => 'Speed']));

    $this->get('/blog/tag/speed')->assertOk()->assertSee('content="noindex, follow"', false);
    $this->get('/blog')->assertOk()->assertSee('content="index, follow', false);
    $this->get('/blog?page=2')->assertSee('content="noindex, follow"', false);
});

it('renders author profile pages with Person schema', function () {
    $post = publishedPost();

    $this->get('/author/'.$post->author->slug)->assertOk()
        ->assertSee('<h1>Ada Writer</h1>', false)
        ->assertSee('"@type":"ProfilePage"', false)
        ->assertSee('"@type":"Person"', false);
});

it('logs 404s, renders a themed page and follows admin redirects', function () {
    $this->get('/missing-page')->assertNotFound()->assertSee(__('larawebkit-front.not_found_title'));
    $this->get('/missing-page');
    expect(NotFoundLog::where('path', '/missing-page')->value('hits'))->toBe(2);

    Redirect::create(['from_path' => '/missing-page', 'to_url' => '/blog', 'status_code' => 302]);
    $this->get('/missing-page?x=1')->assertRedirect('/blog?x=1')->assertStatus(302);

    Redirect::create(['from_path' => '/gone', 'status_code' => 410]);
    $this->get('/gone')->assertStatus(410);
});

it('serves anonymous visitors from the page cache and busts it on save', function () {
    $post = publishedPost();

    $this->get('/blog/hello-seo')->assertHeader('X-Page-Cache', 'MISS');
    $this->get('/blog/hello-seo')->assertHeader('X-Page-Cache', 'HIT');

    $post->update(['title' => 'Changed title']);
    $this->get('/blog/hello-seo')->assertHeader('X-Page-Cache', 'MISS')->assertSee('Changed title');

    $this->actingAs(editor())->get('/blog/hello-seo')->assertHeaderMissing('X-Page-Cache');
});

it('applies canonical host, https and trailing slash redirects', function () {
    config(['larawebkit-seo.enforce_in_tests' => true]);
    app(SettingsRepository::class)->set('seo.canonical_host', 'example.com');
    app(SettingsRepository::class)->set('seo.force_https', true);

    $this->get('http://www.example.com/blog/')->assertRedirect('https://example.com/blog')->assertStatus(301);
});

it('shows only a teaser for members-only posts', function () {
    publishedPost(['members_only' => true, 'excerpt' => 'Teaser text.', 'content' => '<p>Secret body.</p>']);

    $this->get('/blog/hello-seo')->assertOk()->assertSee('Teaser text.')->assertDontSee('Secret body.')
        ->assertSee(__('larawebkit-front.members_only'));
});

it('switches themes from the settings', function () {
    publishedPost();
    foreach (['minimal', 'business', 'docs'] as $theme) {
        app(SettingsRepository::class)->set('general.theme', $theme);
        $this->get('/')->assertOk()->assertSee('data-theme-name="'.$theme.'"', false);
    }
});
