<?php

namespace App\Modules\Seo;

use App\Modules\Media\ImagePipeline;
use App\Modules\Media\Models\Media;
use Illuminate\Support\Facades\Storage;
use Throwable;

/** Generates (once) a 1200×630 crop of an image for Open Graph / Twitter cards. */
class OgImage
{
    public const WIDTH = 1200;

    public const HEIGHT = 630;

    public function url(Media $media): ?string
    {
        $format = config('larawebkit-media.og_format', 'webp') === 'jpeg' ? 'jpeg' : 'webp';
        $path = $media->directory.'/og-'.self::WIDTH.'x'.self::HEIGHT.'.'.($format === 'jpeg' ? 'jpg' : 'webp');
        $disk = Storage::disk($media->disk);

        if (! $disk->exists($path)) {
            try {
                $source = $media->original_path && Storage::disk($media->original_disk)->exists($media->original_path)
                    ? Storage::disk($media->original_disk)->get($media->original_path)
                    : $disk->get($media->variant('max')['path'] ?? '');
                $pipeline = app(ImagePipeline::class);
                $image = $pipeline->manager()->read((string) $source)->cover(self::WIDTH, self::HEIGHT);
                $quality = (int) config('larawebkit-media.quality', 80);
                $encoded = $format === 'webp' && $pipeline->supportsWebp() ? $image->toWebp(quality: $quality, strip: true) : $image->toJpeg(quality: $quality, strip: true);
                $disk->put($path, (string) $encoded, 'public');
            } catch (Throwable $e) {
                report($e);

                return $media->url(1280);
            }
        }

        return $disk->url($path);
    }
}
