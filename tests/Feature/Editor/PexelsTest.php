<?php

use App\Modules\Content\Filament\Resources\Posts\PostResource;
use App\Modules\Content\Models\Post;
use App\Modules\Editor\Pexels\PexelsClient;
use App\Modules\Media\Models\Media;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['services.pexels.key' => 'test-pexels-key']);
    Http::fake([
        'api.pexels.com/v1/search*' => Http::response(['photos' => [[
            'id' => 42, 'width' => 4000, 'height' => 3000, 'alt' => 'Coffee cup', 'url' => 'https://www.pexels.com/photo/42/',
            'photographer' => 'Jane Doe', 'photographer_url' => 'https://www.pexels.com/@jane', 'src' => ['medium' => 'https://images.pexels.com/photos/42/m.jpeg'],
        ]], 'next_page' => 'https://api.pexels.com/v1/search?page=2']),
        'api.pexels.com/v1/photos/42' => Http::response([
            'id' => 42, 'alt' => 'Coffee cup', 'url' => 'https://www.pexels.com/photo/42/', 'photographer' => 'Jane Doe',
            'photographer_url' => 'https://www.pexels.com/@jane', 'src' => ['original' => 'https://images.pexels.com/photos/42/original.jpeg'],
        ]),
        'images.pexels.com/*' => Http::response(fakeJpeg(2000, 1200), 200, ['Content-Type' => 'image/jpeg']),
    ]);
});

it('is hidden without an API key', function () {
    config(['services.pexels.key' => null]);
    expect(app(PexelsClient::class)->enabled())->toBeFalse();

    $this->actingAs(editor())->getJson(route('larawebkit.pexels.search', ['q' => 'x']))->assertForbidden();
    $this->get(PostResource::getUrl('create'))->assertDontSee('larawebkit/pexels/search');
});

it('searches with orientation and pagination', function () {
    $this->actingAs(editor())->getJson(route('larawebkit.pexels.search', ['q' => 'coffee', 'orientation' => 'landscape', 'page' => 1]))
        ->assertOk()->assertJsonPath('photos.0.id', '42')->assertJsonPath('photos.0.photographer', 'Jane Doe')->assertJsonPath('has_more', true);

    Http::assertSent(fn ($r) => str_contains($r->url(), 'orientation=landscape') && $r->hasHeader('Authorization', 'test-pexels-key'));
});

it('imports a photo as WebP with stock sizes and stores attribution', function () {
    $this->actingAs(editor())->postJson(route('larawebkit.pexels.import'), ['id' => '42'])
        ->assertCreated()->assertJsonPath('data.photographer', 'Jane Doe')->assertJsonPath('data.format', 'webp');

    $media = Media::sole();
    expect($media->source)->toBe('pexels')
        ->and($media->source_url)->toBe('https://www.pexels.com/photo/42/')
        ->and($media->photographer_url)->toBe('https://www.pexels.com/@jane')
        ->and(array_keys($media->variants))->toBe([800, 1600, 'thumb']);
});

it('reuses an earlier import and refuses the same photo twice on one post', function () {
    $this->actingAs(editor());
    $this->postJson(route('larawebkit.pexels.import'), ['id' => '42'])->assertCreated();
    $this->postJson(route('larawebkit.pexels.import'), ['id' => '42'])->assertCreated();
    expect(Media::count())->toBe(1);

    $post = Post::factory()->create(['featured_media_id' => Media::sole()->id]);
    $this->postJson(route('larawebkit.pexels.import'), ['id' => '42', 'usable_type' => 'post', 'usable_id' => $post->id])->assertStatus(409);
    $this->getJson(route('larawebkit.pexels.search', ['q' => 'coffee', 'usable_type' => 'post', 'usable_id' => $post->id]))
        ->assertJsonPath('photos.0.used_here', true);
});

it('suggests the title as search query when no AI is configured', function () {
    $this->actingAs(editor())->postJson(route('larawebkit.pexels.suggest'), ['title' => 'Morning coffee rituals'])
        ->assertOk()->assertJsonPath('query', 'Morning coffee rituals');
});

it('shows the attribution on the front end', function () {
    $this->actingAs(editor())->postJson(route('larawebkit.pexels.import'), ['id' => '42']);
    Post::factory()->published()->create(['slug' => 'with-photo', 'featured_media_id' => Media::sole()->id]);
    auth()->logout();

    $this->get('/blog/with-photo')->assertOk()->assertSee('Jane Doe')->assertSee('https://www.pexels.com/@jane', false);
});
