<?php

namespace App\Modules\Media;

use App\Modules\Media\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use InvalidArgumentException;

/**
 * The single entry point for images: store the original privately, then
 * write WebP width variants (never upscaled, metadata stripped) and a square
 * thumbnail to the public disk. Works with GD or Imagick.
 */
class ImagePipeline
{
    private ?ImageManager $manager = null;

    public function manager(): ImageManager
    {
        if ($this->manager) {
            return $this->manager;
        }

        $driver = config('larawebkit-media.driver', 'auto');
        $imagickOk = extension_loaded('imagick') && class_exists(\Imagick::class) && in_array('WEBP', \Imagick::queryFormats('WEBP'), true);

        $use = match ($driver) {
            'imagick' => ImagickDriver::class,
            'gd' => GdDriver::class,
            default => $imagickOk ? ImagickDriver::class : GdDriver::class,
        };

        // Orientation is applied after the first downscale (see writeVariants), so a rotated
        // phone photo never needs a second full-size copy in memory.
        return $this->manager = new ImageManager(new $use, autoOrientation: false, strip: true);
    }

    public static function available(): bool
    {
        return extension_loaded('gd') || extension_loaded('imagick');
    }

    public function supportsWebp(): bool
    {
        return $this->manager()->driver()->supports('webp');
    }

    /**
     * @param  array<string, mixed>  $attributes  title, alt, caption, source, source_id, source_url, photographer, photographer_url, uploaded_by
     * @param  list<int>|null  $widths
     */
    public function store(string $binary, string $originalName, array $attributes = [], ?array $widths = null): Media
    {
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($binary) ?: 'application/octet-stream';
        if (! in_array($mime, (array) config('larawebkit-media.accepted_mime_types'), true)) {
            throw new InvalidArgumentException(__('larawebkit-media.errors.type', ['type' => $mime]));
        }

        $info = app(MemoryBudget::class)->assertCanProcess($binary, $mime);
        $image = $this->manager()->read($binary);
        $uuid = (string) Str::uuid();
        $base = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) ?: 'image';
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) ?: Str::after($mime, '/');
        $originalPath = trim(config('larawebkit-media.original_directory'), '/')."/$uuid/$base.$extension";

        Storage::disk(config('larawebkit-media.original_disk'))->put($originalPath, $binary);

        $media = new Media([
            'uuid' => $uuid,
            'disk' => config('larawebkit-media.disk'),
            'directory' => trim(config('larawebkit-media.directory'), '/').'/'.now()->format('Y/m')."/$uuid",
            'original_disk' => config('larawebkit-media.original_disk'),
            'original_path' => $originalPath,
            'file_name' => mb_substr($base.'.'.$extension, 0, 255),
            'mime_type' => $mime,
            'width' => $info['width'],
            'height' => $info['height'],
            'title' => $attributes['title'] ?? Str::headline($base),
            ...array_intersect_key($attributes, array_flip(['alt', 'caption', 'source', 'source_id', 'source_url', 'photographer', 'photographer_url', 'uploaded_by'])),
        ]);
        $media->format = $this->outputFormat($mime);

        $this->writeVariants($media, $image, $info, $widths);
        unset($image);
        $media->save();

        return $media;
    }

    /** @param  array<string, mixed>  $attributes */
    public function storeUpload(UploadedFile $file, array $attributes = []): Media
    {
        $maxKb = (int) config('larawebkit-media.max_upload_kb');
        if ($file->getSize() > $maxKb * 1024) {
            throw new InvalidArgumentException(__('larawebkit-media.errors.size', ['max' => round($maxKb / 1024, 1)]));
        }

        return $this->store((string) file_get_contents($file->getRealPath()), $file->getClientOriginalName(), $attributes);
    }

    /** Rebuild all variants from the private original (after a config change). */
    public function regenerate(Media $media, ?array $widths = null): Media
    {
        $originals = Storage::disk($media->original_disk);
        if (! $media->original_path || ! $originals->exists($media->original_path)) {
            throw new InvalidArgumentException(__('larawebkit-media.errors.original_missing', ['id' => $media->id]));
        }

        $binary = $originals->get($media->original_path);
        $info = app(MemoryBudget::class)->assertCanProcess($binary, $media->mime_type);
        $this->deleteVariants($media);
        $image = $this->manager()->read($binary);
        unset($binary);
        $media->format = $this->outputFormat($media->mime_type);
        [$media->width, $media->height] = [$info['width'], $info['height']];
        $this->writeVariants($media, $image, $info, $widths ?? ($media->isStock() ? config('larawebkit-media.stock_widths') : null));
        unset($image);
        $media->save();

        return $media;
    }

    public function deleteFiles(Media $media): void
    {
        $this->deleteVariants($media);
        if ($media->original_path) {
            Storage::disk($media->original_disk)->deleteDirectory(dirname($media->original_path));
        }
    }

    private function deleteVariants(Media $media): void
    {
        if ($media->directory) {
            Storage::disk($media->disk)->deleteDirectory($media->directory);
        }
        $media->variants = [];
    }

    private function outputFormat(string $sourceMime): string
    {
        if ($this->supportsWebp()) {
            return 'webp';
        }

        return $sourceMime === 'image/png' || $sourceMime === 'image/gif' ? 'png' : 'jpeg';
    }

    /**
     * Largest variant first, downscaling the decoded image in place, then every smaller
     * variant from the previous one. Only one full-size bitmap ever exists, and the
     * thumbnail is cut from a copy of at most 640 px.
     *
     * @param  array{width: int, height: int, orientation: int}  $info  logical (upright) size and EXIF orientation
     * @param  list<int>|null  $widths
     */
    private function writeVariants(Media $media, ImageInterface $image, array $info, ?array $widths): void
    {
        $widths = array_values(array_unique(array_map('intval', $widths ?? (array) config('larawebkit-media.widths'))));
        sort($widths);
        $originalWidth = $info['width'];

        // Never upscale: keep widths below the original, plus the original width itself as the largest.
        $targets = array_values(array_filter($widths, fn (int $w) => $w < $originalWidth));
        $targets[] = min($originalWidth, max($widths) ?: $originalWidth);
        $targets = array_values(array_unique($targets));
        rsort($targets);

        $disk = Storage::disk($media->disk);
        $base = pathinfo($media->file_name, PATHINFO_FILENAME);
        $thumbSize = (int) config('larawebkit-media.thumbnail', 300);
        $variants = [];
        $square = null;

        foreach ($targets as $i => $width) {
            if ($i === 0) {
                // Quarter-turned photos (EXIF 5-8) are stored sideways: their upright width is the stored height.
                $info['orientation'] >= 5 ? $image->scaleDown(height: $width) : $image->scaleDown(width: $width);
                if ($info['orientation'] > 1) {
                    $image->orient();
                }
            } else {
                $image->scaleDown(width: $width);
            }

            $encoded = $this->encode($image, $media->format);
            $path = "{$media->directory}/{$base}-{$width}.".$this->extension($media->format);
            $disk->put($path, $encoded, 'public');
            $variants[(string) $width] = ['path' => $path, 'width' => $image->width(), 'height' => $image->height(), 'size' => strlen($encoded)];
            unset($encoded);

            // Cut the square thumbnail from the first copy that is small enough (or the last one).
            if ($square === null && ($width <= max(640, $thumbSize) || $i === count($targets) - 1)) {
                $square = (clone $image)->coverDown($thumbSize, $thumbSize);
                $thumb = $this->encode($square, $media->format);
                $thumbPath = "{$media->directory}/{$base}-thumb.".$this->extension($media->format);
                $disk->put($thumbPath, $thumb, 'public');
                $variants['thumb'] = ['path' => $thumbPath, 'width' => $square->width(), 'height' => $square->height(), 'size' => strlen($thumb)];
                unset($thumb);
            }
        }
        unset($square);
        gc_collect_cycles();

        ksort($variants, SORT_NATURAL);
        $thumbVariant = $variants['thumb'];
        unset($variants['thumb']);
        $variants['thumb'] = $thumbVariant;

        $media->variants = $variants;
        $media->size = $variants[(string) $targets[0]]['size'];
    }

    private function encode(ImageInterface $image, string $format): string
    {
        $quality = (int) config('larawebkit-media.quality', 80);

        return (string) match ($format) {
            'webp' => $image->toWebp(quality: $quality, strip: true),
            'png' => $image->toPng(),
            default => $image->toJpeg(quality: $quality, strip: true),
        };
    }

    private function extension(string $format): string
    {
        return $format === 'jpeg' ? 'jpg' : $format;
    }
}
