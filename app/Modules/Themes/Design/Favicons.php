<?php

namespace App\Modules\Themes\Design;

use App\Modules\Media\ImagePipeline;
use App\Modules\Media\MemoryBudget;
use App\Modules\Media\Models\Media;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Throwable;

/**
 * Generates the favicon set from the image chosen in Appearance: favicon.ico
 * (16/32/48), a 32 px PNG, the 180 px Apple touch icon and 192/512 px icons
 * for the web manifest. Icons are PNG on purpose (browsers and iOS require
 * it); everything else on the site stays WebP.
 */
class Favicons
{
    public const SIZES = ['icon-32.png' => 32, 'apple-touch-icon.png' => 180, 'icon-192.png' => 192, 'icon-512.png' => 512];

    public function __construct(private ImagePipeline $pipeline) {}

    /** @return array<string, string>|null file name => public URL */
    public function urls(): ?array
    {
        $media = $this->media();
        if (! $media) {
            return null;
        }

        return Cache::rememberForever($this->cacheKey($media), fn () => $this->generate($media));
    }

    public function forget(): void
    {
        if ($media = $this->media()) {
            Cache::forget($this->cacheKey($media));
        }
    }

    /** @return array<string, string>|null */
    public function generate(Media $media): ?array
    {
        try {
            $binary = $this->source($media);
            if ($binary === null) {
                return null;
            }
            app(MemoryBudget::class)->assertCanProcess($binary, $media->mime_type);
            $disk = Storage::disk($media->disk);
            $dir = 'branding/favicon-'.$media->id.'-'.$media->updated_at?->timestamp;
            $urls = [];

            $image = $this->pipeline->manager()->read($binary);
            unset($binary);
            $image->coverDown(512, 512);

            foreach (array_reverse(self::SIZES, true) as $file => $size) {
                $copy = (clone $image)->resize($size, $size);
                if ($file === 'apple-touch-icon.png') {
                    // iOS paints transparent areas black: put the icon on the page background.
                    $copy = $this->pipeline->manager()->create($size, $size)->fill('#ffffff')->place($copy);
                }
                $disk->put("$dir/$file", (string) $copy->toPng(), 'public');
                $urls[$file] = $disk->url("$dir/$file");
            }

            $entries = [];
            foreach ([16, 32, 48] as $size) {
                $entries[$size] = (string) (clone $image)->resize($size, $size)->toPng();
            }
            $disk->put("$dir/favicon.ico", self::ico($entries), 'public');
            $urls['favicon.ico'] = $disk->url("$dir/favicon.ico");

            return $urls;
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    public function tags(): HtmlString
    {
        $urls = $this->urls();
        if (! $urls) {
            return new HtmlString('<link rel="icon" href="'.e(asset('favicon.ico')).'" sizes="any">');
        }

        return new HtmlString(
            '<link rel="icon" href="'.e($urls['favicon.ico']).'" sizes="48x48">'
            .'<link rel="icon" href="'.e($urls['icon-32.png']).'" type="image/png" sizes="32x32">'
            .'<link rel="apple-touch-icon" href="'.e($urls['apple-touch-icon.png']).'">'
        );
    }

    /**
     * A minimal ICO container with PNG-compressed entries (supported by every
     * current browser and Windows since Vista).
     *
     * @param  array<int, string>  $pngs  size => PNG bytes
     */
    public static function ico(array $pngs): string
    {
        $header = pack('vvv', 0, 1, count($pngs));
        $directory = '';
        $data = '';
        $offset = 6 + 16 * count($pngs);
        foreach ($pngs as $size => $png) {
            $directory .= pack('CCCCvvVV', $size >= 256 ? 0 : $size, $size >= 256 ? 0 : $size, 0, 0, 1, 32, strlen($png), $offset);
            $data .= $png;
            $offset += strlen($png);
        }

        return $header.$directory.$data;
    }

    private function media(): ?Media
    {
        $id = setting('appearance.favicon');

        return $id ? Media::find($id) : null;
    }

    private function cacheKey(Media $media): string
    {
        return 'larawebkit.favicons.'.$media->id.'.'.$media->updated_at?->timestamp;
    }

    /** Prefer the private original (sharper, may have transparency), else the largest variant. */
    private function source(Media $media): ?string
    {
        $originals = Storage::disk($media->original_disk);
        if ($media->original_path && $originals->exists($media->original_path)) {
            return $originals->get($media->original_path);
        }
        $variant = $media->variant();

        return $variant ? Storage::disk($media->disk)->get($variant['path']) : null;
    }
}
