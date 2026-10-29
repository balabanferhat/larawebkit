<?php

use App\Modules\Content\Models\Category;
use App\Modules\Content\Models\Post;
use App\Modules\Settings\SettingsRepository;

beforeEach(function () {
    app(SettingsRepository::class)->set('features.page_cache', false);
});

it('shows share links, reading time, a table of contents, previous / next and related posts in every theme', function (string $theme) {
    app(SettingsRepository::class)->set('general.theme', $theme);
    $news = Category::create(['name' => 'News', 'slug' => 'news']);
    $older = Post::factory()->published()->create(['title' => 'Older story', 'published_at' => now()->subDays(3)]);
    $post = Post::factory()->published()->create(['title' => 'Middle story', 'slug' => 'middle', 'published_at' => now()->subDays(2),
        'content' => '<h2>One</h2><p>a</p><h2>Two</h2><p>b</p><h2>Three</h2><p>c</p>']);
    $newer = Post::factory()->published()->create(['title' => 'Newer story', 'published_at' => now()->subDay()]);
    $post->categories()->attach($news);
    $newer->categories()->attach($news);

    $html = $this->get('/blog/middle')->assertOk()->getContent();
    $url = rawurlencode(url('/blog/middle'));

    expect($html)
        ->toContain('class="lwk-share"')
        ->toContain('https://x.com/intent/post?url='.$url)
        ->toContain('https://www.linkedin.com/sharing/share-offsite/?url='.$url)
        ->toContain('mailto:?subject=')
        ->toContain('data-lwk-share-copy')
        ->not->toContain('platform.twitter.com')->not->toContain('connect.facebook.net')
        ->toContain('class="lwk-toc"')
        ->toContain(__('larawebkit-front.reading_time', ['minutes' => 1]))
        ->toContain('rel="prev" class="lwk-adjacent-prev"')->toContain('Older story')
        ->toContain('rel="next" class="lwk-adjacent-next"')->toContain('Newer story')
        ->toContain('id="related"');
})->with(['minimal', 'business', 'personal', 'magazine', 'docs']);

it('fills related posts with the latest ones and hides the share links when turned off', function () {
    $post = Post::factory()->published()->create(['slug' => 'alone', 'published_at' => now()->subDay()]);
    Post::factory()->published()->count(2)->create(['published_at' => now()->subDays(2)]);

    expect($post->related())->toHaveCount(2)
        ->and($post->adjacent()['next'])->toBeNull()
        ->and($post->adjacent()['previous'])->not->toBeNull();

    app(SettingsRepository::class)->set('general.share_buttons', false);
    $this->get('/blog/alone')->assertOk()->assertDontSee('class="lwk-share"', false);
});

it('keeps previous / next within the language and published posts', function () {
    $post = Post::factory()->published()->create(['published_at' => now()->subDays(2)]);
    Post::factory()->create(['status' => 'draft', 'published_at' => null]);
    Post::factory()->published()->create(['locale' => 'tr', 'published_at' => now()->subDay()]);
    Post::factory()->published()->create(['published_at' => now()->addDay()]); // scheduled

    expect($post->adjacent())->toBe(['previous' => null, 'next' => null]);
});
