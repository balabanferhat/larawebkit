<?php

use App\Modules\Media\ImagePipeline;
use App\Modules\Media\MemoryBudget;
use App\Modules\Media\Models\Media;
use App\Modules\Media\Support\ContentImages;
use App\Modules\Media\Support\ResponsiveImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('converts uploads to WebP width variants without upscaling and keeps the original privately', function () {
    $media = app(ImagePipeline::class)->store(fakeJpeg(1400, 800), 'Holiday Photo.jpg', ['alt' => 'Beach']);

    expect($media->format)->toBe('webp')
        ->and($media->width)->toBe(1400)
        ->and(array_keys($media->variants))->toBe([320, 640, 960, 1280, 1400, 'thumb']);

    foreach ($media->variants as $key => $variant) {
        Storage::disk('public')->assertExists($variant['path']);
        expect($variant['path'])->toEndWith('.webp');
        $info = getimagesizefromstring(Storage::disk('public')->get($variant['path']));
        expect($info['mime'])->toBe('image/webp');
        if ($key !== 'thumb') {
            expect($info[0])->toBe((int) $key)->and($info[0])->toBeLessThanOrEqual(1400);
        }
    }
    expect($media->variants['thumb']['width'])->toBe(300)->and($media->variants['thumb']['height'])->toBe(300)
        ->and($media->variants['640']['height'])->toBe(365);

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

it('turns EXIF-rotated photos upright and sizes variants by their upright width', function () {
    // Stored landscape 800×600 with orientation 6 (rotate 90° clockwise) = upright portrait 600×800.
    $media = app(ImagePipeline::class)->store(jpegWithOrientation(800, 600, 6), 'phone.jpg');

    expect([$media->width, $media->height])->toBe([600, 800])
        ->and(array_keys($media->variants))->toBe([320, 600, 'thumb'])
        ->and([$media->variants['600']['width'], $media->variants['600']['height']])->toBe([600, 800])
        ->and([$media->variants['320']['width'], $media->variants['320']['height']])->toBe([320, 427]);

    $info = getimagesizefromstring(Storage::disk('public')->get($media->variants['600']['path']));
    expect([$info[0], $info[1]])->toBe([600, 800]);
});

it('refuses images above the megapixel cap before decoding them', function () {
    config(['larawebkit-media.max_input_megapixels' => 24]);
    $bomb = jpegClaimingSize(8000, 6000); // 48 MP header, a few hundred bytes of data

    expect(fn () => app(ImagePipeline::class)->store($bomb, 'huge.jpg'))
        ->toThrow(InvalidArgumentException::class, '8000 × 6000 px (48 MP); the maximum is 24 MP');
    expect(Media::count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBe([]);
});

it('refuses images that would not fit into memory_limit, with a clear message', function () {
    config(['larawebkit-media.max_input_megapixels' => 0]);
    $previous = ini_set('memory_limit', (string) (memory_get_usage(true) + 80 * 1024 * 1024));

    try {
        expect(fn () => app(ImagePipeline::class)->store(jpegClaimingSize(6000, 4000), 'phone-24mp.jpg'))
            ->toThrow(InvalidArgumentException::class, 'too large for this server');
        // A normal web image still goes through under the same limit.
        expect(app(ImagePipeline::class)->store(fakeJpeg(800, 500), 'ok.jpg')->format)->toBe('webp');
    } finally {
        ini_set('memory_limit', $previous);
    }
});

it('returns a 422 with the reason when an upload is too large', function () {
    config(['larawebkit-media.max_input_megapixels' => 10]);
    $file = UploadedFile::fake()->createWithContent('big.jpg', jpegClaimingSize(5000, 4000));

    $this->actingAs(editor())->postJson(route('larawebkit.media.store'), ['file' => $file])
        ->assertUnprocessable()
        ->assertJsonPath('errors.file.0', fn ($m) => str_contains($m, '20 MP') && str_contains($m, 'maximum is 10 MP'));
});

it('budgets a 24 MP phone photo within 256M but not within 128M', function () {
    $budget = app(MemoryBudget::class);
    $phone = $budget->estimate(6000, 4000, 1, 8 * 1024 * 1024);
    $base = 64 * 1024 * 1024; // framework + request

    expect($phone)->toBeLessThan(256 * 1024 * 1024 - $base - MemoryBudget::SAFETY_MARGIN)
        ->and($phone)->toBeGreaterThan(128 * 1024 * 1024 - $base - MemoryBudget::SAFETY_MARGIN)
        ->and(MemoryBudget::memoryLimitBytes('256M'))->toBe(268435456)
        ->and(MemoryBudget::memoryLimitBytes('-1'))->toBeNull()
        ->and(MemoryBudget::maxMegapixelsFor(192 * 1024 * 1024))->toBeGreaterThan(24.0);
});
