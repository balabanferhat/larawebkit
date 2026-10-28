<?php

use App\Modules\Media\ImagePipeline;
use App\Modules\Media\Support\ContentImages;
use App\Modules\Media\Support\ResponsiveImage;
use Illuminate\Support\Facades\Storage;

it('converts uploads to WebP width variants without upscaling and keeps the original privately', function () {
    $media = app(ImagePipeline::class)->store(fakeJpeg(1500, 1000), 'Holiday Photo.jpg', ['alt' => 'Beach']);

    expect($media->format)->toBe('webp')
        ->and($media->width)->toBe(1500)
        ->and(array_keys($media->variants))->toBe([320, 640, 960, 1280, 1500, 'thumb']);

    foreach ($media->variants as $key => $variant) {
        Storage::disk('public')->assertExists($variant['path']);
        expect($variant['path'])->toEndWith('.webp');
        $info = getimagesizefromstring(Storage::disk('public')->get($variant['path']));
        expect($info['mime'])->toBe('image/webp');
        if ($key !== 'thumb') {
            expect($info[0])->toBe((int) $key)->and($info[0])->toBeLessThanOrEqual(1500);
        }
    }
    expect($media->variants['thumb']['width'])->toBe(300)->and($media->variants['thumb']['height'])->toBe(300)
        ->and($media->variants['640']['height'])->toBe(427);

    Storage::disk('local')->assertExists($media->original_path);
    expect($media->original_path)->not->toStartWith('media/');
});

it('only creates the original width for small images', function () {
    $media = app(ImagePipeline::class)->store(fakeJpeg(200, 100), 'small.jpg');

    expect(array_keys($media->variants))->toBe([200, 'thumb']);
});

it('strips EXIF metadata from variants', function () {
    // JPEG with an APP1 EXIF segment injected after SOI.
    $jpeg = fakeJpeg(800, 600);
    $exif = "Exif\x00\x00MM\x00\x2a\x00\x00\x00\x08\x00\x00GPS-SECRET-LOCATION";
    $jpeg = substr($jpeg, 0, 2)."\xFF\xE1".pack('n', strlen($exif) + 2).$exif.substr($jpeg, 2);

    $media = app(ImagePipeline::class)->store($jpeg, 'gps.jpg');

    foreach ($media->variants as $variant) {
        expect(Storage::disk('public')->get($variant['path']))->not->toContain('GPS-SECRET-LOCATION');
    }
});

it('rejects non-image files', function () {
    app(ImagePipeline::class)->store('<?php echo 1;', 'evil.php.jpg');
})->throws(InvalidArgumentException::class);

it('falls back to JPEG when the driver cannot write WebP', function () {
    $pipeline = new class extends ImagePipeline
    {
        public function supportsWebp(): bool
        {
            return false;
        }
    };
    $media = $pipeline->store(fakeJpeg(700, 400), 'old-host.jpg');

    expect($media->format)->toBe('jpeg')->and($media->variants['640']['path'])->toEndWith('.jpg');
});

it('regenerates variants after a config change', function () {
    $media = app(ImagePipeline::class)->store(fakeJpeg(1200, 800), 'r.jpg');
    config(['larawebkit-media.widths' => [400, 800]]);

    $this->artisan('larawebkit:media:regenerate')->assertSuccessful();

    expect(array_keys($media->fresh()->variants))->toBe([400, 800, 'thumb']);
});

it('deletes files with the media item', function () {
    $media = app(ImagePipeline::class)->store(fakeJpeg(700, 400), 'gone.jpg');
    $paths = array_column($media->variants, 'path');
    $media->delete();

    foreach ($paths as $path) {
        Storage::disk('public')->assertMissing($path);
    }
    Storage::disk('local')->assertMissing($media->original_path);
});

it('renders responsive img tags with srcset, dimensions and lazy loading', function () {
    $media = app(ImagePipeline::class)->store(fakeJpeg(1000, 500), 'tag.jpg', ['alt' => 'A "quoted" alt']);

    $lazy = (string) ResponsiveImage::tag($media);
    expect($lazy)->toContain('srcset="')->toContain(' 320w')->toContain('width="1000"')->toContain('height="500"')
        ->toContain('loading="lazy"')->toContain('decoding="async"')->toContain('alt="A &quot;quoted&quot; alt"')
        ->not->toContain('fetchpriority');

    $hero = (string) ResponsiveImage::tag($media, priority: true);
    expect($hero)->toContain('fetchpriority="high"')->not->toContain('loading="lazy"');
    expect((string) ResponsiveImage::preload($media))->toContain('rel="preload"')->toContain('imagesrcset=');
});

it('rewrites editor images into responsive tags at render time', function () {
    $media = app(ImagePipeline::class)->store(fakeJpeg(1000, 500), 'inline.jpg');
    $html = '<p>Hi</p><img src="/old.webp" alt="Inline" data-id="'.$media->id.'"><img src="https://x.test/a.png" alt="ext">';

    $out = ContentImages::render($html);

    expect($out)->toContain('srcset=')->toContain('alt="Inline"')->toContain('width="1000"')
        ->toContain('<img loading="lazy" decoding="async" src="https://x.test/a.png"')
        ->and(ContentImages::ids($html))->toBe([$media->id]);
});

it('stores and shows Pexels attribution', function () {
    $media = app(ImagePipeline::class)->store(fakeJpeg(900, 600), 'pexels-1.jpg', [
        'source' => 'pexels', 'source_id' => '1', 'source_url' => 'https://www.pexels.com/photo/1/',
        'photographer' => 'Jane Doe', 'photographer_url' => 'https://www.pexels.com/@jane',
    ]);

    $credit = (string) ResponsiveImage::attribution($media);
    expect($credit)->toContain('Jane Doe')->toContain('https://www.pexels.com/@jane')->toContain('Pexels');
});
