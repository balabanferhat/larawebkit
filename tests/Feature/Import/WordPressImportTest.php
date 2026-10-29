<?php

use App\Models\User;
use App\Modules\Content\Models\Category;
use App\Modules\Content\Models\Page;
use App\Modules\Content\Models\Post;
use App\Modules\Content\Models\Redirect;
use App\Modules\Content\Models\Tag;
use App\Modules\Media\Models\Media;
use App\Modules\Settings\SettingsRepository;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

function wpFixture(string $name): array
{
    return json_decode(file_get_contents(base_path("tests/fixtures/wordpress/$name.json")), true);
}

function wpJpeg(): string
{
    $image = imagecreatetruecolor(40, 30);
    imagefill($image, 0, 0, imagecolorallocate($image, 200, 40, 40));
    ob_start();
    imagejpeg($image);

    return (string) ob_get_clean();
}

function fakeWordPress(array $override = []): void
{
    // Http::fake() stacks stubs (the first match wins), so later calls only swap the fixtures.
    $GLOBALS['wpOverride'] = $override;
    if (($GLOBALS['wpFaked'] ?? null) === app()) {
        return;
    }
    $GLOBALS['wpFaked'] = app();
    Http::fake(function (Request $request) {
        $override = $GLOBALS['wpOverride'];
        $url = $request->url();
        $path = (string) parse_url($url, PHP_URL_PATH);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        if (! str_starts_with($url, 'https://example.com/')) {
            return Http::response('nope', 404);
        }
        if (preg_match('#/wp-json/wp/v2/media/(\d+)$#', $path, $m)) {
            return is_file(base_path("tests/fixtures/wordpress/media-{$m[1]}.json")) ? Http::response(wpFixture("media-{$m[1]}")) : Http::response(['code' => 'rest_post_invalid_id'], 404);
        }
        if (preg_match('#/wp-json/wp/v2/(\w+)$#', $path, $m)) {
            $kind = $m[1];
            if ($kind === 'posts') {
                $page = (int) ($query['page'] ?? 1);

                return $page <= 2 ? Http::response($override['posts-'.$page] ?? wpFixture('posts-'.$page), 200, ['X-WP-TotalPages' => '2']) : Http::response(['code' => 'rest_post_invalid_page_number'], 400);
            }

            return Http::response($override[$kind] ?? wpFixture($kind), 200, ['X-WP-TotalPages' => '1']);
        }
        if (str_contains($path, '/wp-content/uploads/')) {
            return Http::response(wpJpeg(), 200, ['Content-Type' => 'image/jpeg']);
        }

        return Http::response('', 404);
    });
}

beforeEach(function () {
    app(SettingsRepository::class)->set('features.page_cache', false);
});

it('imports posts, pages, terms, authors and images from the REST API', function () {
    fakeWordPress();

    $this->artisan('larawebkit:import:wordpress', ['source' => 'https://example.com'])->assertSuccessful();

    expect(Post::count())->toBe(3)->and(Page::count())->toBe(2)
        ->and(Category::pluck('name')->sort()->values()->all())->toBe(['Gardening & Plants', 'Private notes', 'Uncategorized'])
        ->and(Tag::sole()->slug)->toBe('tomatoes');

    $post = Post::where('slug', 'hello-tomatoes')->sole();
    expect($post->title)->toBe('Hello “tomatoes”')
        ->and($post->status)->toBe('published')
        ->and($post->published_at->utc()->toDateTimeString())->toBe('2024-05-10 06:30:00')
        ->and($post->excerpt)->toBe('Tomatoes love the sun. See about us.')
        ->and($post->author->name)->toBe("Jane D'Arc")
        ->and($post->categories->pluck('slug')->all())->toBe(['gardening'])
        ->and($post->tags->pluck('slug')->all())->toBe(['tomatoes'])
        ->and($post->featuredMedia->alt)->toBe('A raised garden bed')
        ->and($post->announced_at)->not->toBeNull();

    // Clean markup: no classes, styles, scripts or iframes; internal links relative; headings from h2.
    $content = $post->content;
    expect($content)->not->toContain('class="wp-')->not->toContain('<script')->not->toContain('<iframe')->not->toContain('srcset')
        ->toContain('<a href="/about/">about us</a>')
        ->toContain('<h2>Watering</h2>')
        ->toContain('<a href="https://www.youtube.com/watch?v&#61;abc123">')
        ->toContain('<figcaption>Our harvest</figcaption>')
        ->toContain('src="https://cdn.othersite.net/photo.jpg"'); // other hosts are not downloaded

    $tomato = Media::where('source_url', 'https://example.com/wp-content/uploads/2024/05/tomato.jpg')->sole();
    expect($content)->toContain('data-id="'.$tomato->id.'"')->and($tomato->source)->toBe('wordpress')->and($tomato->alt)->toBe('Red tomatoes')
        ->and($tomato->mime_type)->toBe('image/jpeg');
    Http::assertNotSent(fn (Request $r) => str_contains($r->url(), '-1024x683'));

    // Pages keep their paths, nested under the parent.
    expect(Page::where('slug', 'team')->sole()->path)->toBe('about/team')
        ->and(Page::where('slug', 'about')->sole()->content)->toContain('<a href="/contact/">Write to us</a>');
    $this->get('/about/team')->assertOk()->assertSee('Two people and a cat.');

    // Old post and archive URLs redirect.
    $this->get('/2024/05/hello-tomatoes/')->assertRedirect('/blog/hello-tomatoes')->assertStatus(301);
    $this->get('/category/gardening/')->assertRedirect('/blog/category/gardening');
    $this->get('/blog/hello-tomatoes')->assertOk()->assertSee('Tomatoes love the sun.');

    // An unknown author is left empty; Turkish slugs are decoded.
    expect(Post::where('title', 'Şekerli domates')->sole())->slug->toBe('sekerli-domates')->author_id->toBeNull();

    // The imported author has a profile but cannot use the admin.
    $jane = User::where('slug', 'jane')->sole();
    expect($jane->canAccessPanel(Filament\Facades\Filament::getPanel('admin')))->toBeFalse();
});

it('is idempotent and updates what changed in WordPress', function () {
    fakeWordPress();
    $this->artisan('larawebkit:import:wordpress', ['source' => 'https://example.com'])->assertSuccessful();
    $counts = [Post::count(), Page::count(), Media::count(), Redirect::count(), User::count(), Category::count()];

    $this->artisan('larawebkit:import:wordpress', ['source' => 'https://example.com'])->assertSuccessful();
    expect([Post::count(), Page::count(), Media::count(), Redirect::count(), User::count(), Category::count()])->toBe($counts);

    // Edited in WordPress → updated here; deleted here → not brought back.
    $posts = wpFixture('posts-1');
    $posts[0]['title']['rendered'] = 'Hello again';
    $posts[0]['modified_gmt'] = '2024-08-01T00:00:00';
    Post::where('slug', 'my-diary')->sole()->delete();
    fakeWordPress(['posts-1' => $posts]);

    $this->artisan('larawebkit:import:wordpress', ['source' => 'https://example.com'])->assertSuccessful();
    expect(Post::where('slug', 'hello-tomatoes')->sole()->title)->toBe('Hello again')
        ->and(Post::count())->toBe(2)->and(Post::withTrashed()->count())->toBe(3);
});

it('changes nothing in a dry run', function () {
    fakeWordPress();

    $this->artisan('larawebkit:import:wordpress', ['source' => 'https://example.com', '--dry-run' => true])
        ->expectsOutputToContain('Dry run')
        ->assertSuccessful();

    expect(Post::count())->toBe(0)->and(Page::count())->toBe(0)->and(Category::count())->toBe(0)->and(Media::count())->toBe(0)
        ->and(Redirect::count())->toBe(0)->and(User::count())->toBe(0);
    Http::assertNotSent(fn (Request $r) => str_contains($r->url(), '/wp-content/uploads/'));
});

it('skips posts in listed categories and can assign one author', function () {
    fakeWordPress();
    $editor = superAdmin(['email' => 'editor@example.com']);

    $this->artisan('larawebkit:import:wordpress', ['source' => 'https://example.com', '--skip-category' => ['private-notes'], '--author' => 'editor@example.com', '--no-images' => true])
        ->assertSuccessful();

    expect(Post::where('slug', 'my-diary')->exists())->toBeFalse()
        ->and(Category::where('slug', 'private-notes')->exists())->toBeFalse()
        ->and(Post::pluck('author_id')->unique()->all())->toBe([$editor->id])
        ->and(User::count())->toBe(1)
        ->and(Media::count())->toBe(0)
        ->and(Post::where('slug', 'hello-tomatoes')->sole()->content)->toContain('src="https://example.com/wp-content/uploads/2024/05/tomato-1024x683.jpg"');
});

it('imports an export file and refuses bad input', function () {
    fakeWordPress();
    $file = storage_path('framework/testing/wp-export.json');
    @mkdir(dirname($file), 0755, true);
    file_put_contents($file, json_encode([
        'site' => 'https://example.com', 'users' => wpFixture('users'), 'categories' => wpFixture('categories'), 'tags' => wpFixture('tags'),
        'pages' => wpFixture('pages'), 'posts' => [...wpFixture('posts-1'), ...wpFixture('posts-2')], 'media' => [wpFixture('media-12'), wpFixture('media-13')],
    ]));

    $this->artisan('larawebkit:import:wordpress', ['source' => $file, '--locale' => 'tr'])->assertSuccessful();
    expect(Post::count())->toBe(3)->and(Post::pluck('locale')->unique()->all())->toBe(['tr']);
    Http::assertNotSent(fn (Request $r) => str_contains($r->url(), '/wp-json/'));
    @unlink($file);

    $this->artisan('larawebkit:import:wordpress', ['source' => base_path('composer.json')])->assertFailed();
    $this->artisan('larawebkit:import:wordpress', ['source' => 'https://example.com', '--author' => 'nobody@example.com'])->assertFailed();
    $this->artisan('larawebkit:import:wordpress', ['source' => 'https://example.com', '--locale' => 'xx'])->assertFailed();
});
