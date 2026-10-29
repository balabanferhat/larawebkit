<?php

namespace App\Modules\Media;

use InvalidArgumentException;

/**
 * Decides, before anything is decoded, whether an image can be processed
 * within this server's memory_limit. Shared hosting often runs PHP with
 * 128-256 MB; a decoded bitmap needs ~4 bytes per pixel, so a 24 MP phone
 * photo alone is ~96 MB. Too-large images are refused with a clear message
 * instead of a fatal "Allowed memory size exhausted".
 */
class MemoryBudget
{
    /** Bytes per decoded pixel (truecolor + alpha) for GD and Imagick (Q8). */
    public const BYTES_PER_PIXEL = 4;

    /** Allocator / row-pointer overhead on top of the raw bitmap. */
    public const OVERHEAD = 1.15;

    /** Kept free for the framework, the request and the encoder buffers. */
    public const SAFETY_MARGIN = 24 * 1024 * 1024;

    /**
     * Reads dimensions from the file header only (no decode) and checks the
     * megapixel cap and the memory estimate.
     *
     * @return array{width: int, height: int, orientation: int, frames: int, megapixels: float, estimated_bytes: int}
     */
    public function assertCanProcess(string $binary, string $mime): array
    {
        $size = @getimagesizefromstring($binary);
        if ($size === false || ($size[0] ?? 0) < 1 || ($size[1] ?? 0) < 1) {
            throw new InvalidArgumentException(__('larawebkit-media.errors.processing'));
        }

        $storedWidth = (int) $size[0];
        $storedHeight = (int) $size[1];
        $orientation = $mime === 'image/jpeg' ? $this->orientation($binary) : 1;
        $frames = $mime === 'image/gif' ? max(1, substr_count($binary, "\x21\xF9\x04")) : 1;
        // Quarter-turned photos (EXIF 5-8) are stored sideways.
        [$width, $height] = $orientation >= 5 ? [$storedHeight, $storedWidth] : [$storedWidth, $storedHeight];
        $megapixels = round($storedWidth * $storedHeight / 1_000_000, 1);

        $maxMegapixels = (float) config('larawebkit-media.max_input_megapixels', 40);
        if ($maxMegapixels > 0 && $megapixels > $maxMegapixels) {
            throw new InvalidArgumentException(__('larawebkit-media.errors.megapixels', [
                'width' => $storedWidth, 'height' => $storedHeight, 'mp' => $megapixels, 'max' => $maxMegapixels,
            ]));
        }

        $estimate = $this->estimate($storedWidth, $storedHeight, $frames, strlen($binary));
        $available = self::available();
        if ($available !== null && $estimate > $available) {
            throw new InvalidArgumentException(__('larawebkit-media.errors.memory', [
                'width' => $storedWidth, 'height' => $storedHeight, 'mp' => $megapixels,
                'max' => self::maxMegapixelsFor($available, $frames),
                'limit' => ini_get('memory_limit'),
            ]));
        }

        return compact('width', 'height', 'orientation', 'frames', 'megapixels') + ['estimated_bytes' => $estimate];
    }

    /**
     * Peak bytes for the pipeline: the decoded original (every GIF frame) plus the
     * largest variant it is downscaled into, plus the compressed input itself.
     */
    public function estimate(int $width, int $height, int $frames = 1, int $inputBytes = 0): int
    {
        $maxVariant = max((array) config('larawebkit-media.widths', [1920])) ?: 1920;
        $variantWidth = min($width, $maxVariant);
        $variantHeight = (int) ceil($height * $variantWidth / max(1, $width));

        $bitmaps = $width * $height * $frames + $variantWidth * $variantHeight;

        return (int) ($bitmaps * self::BYTES_PER_PIXEL * self::OVERHEAD) + $inputBytes * 2;
    }

    /** Free bytes under memory_limit (minus the safety margin), or null when unlimited. */
    public static function available(): ?int
    {
        $limit = self::memoryLimitBytes();
        if ($limit === null) {
            return null;
        }

        return max(0, $limit - memory_get_usage(true) - self::SAFETY_MARGIN);
    }

    /** memory_limit in bytes, null for -1 (unlimited). */
    public static function memoryLimitBytes(?string $value = null): ?int
    {
        $value = trim((string) ($value ?? ini_get('memory_limit')));
        if ($value === '' || $value === '-1') {
            return null;
        }
        $number = (int) $value;

        return match (strtolower(substr($value, -1))) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }

    /** Largest photo (in megapixels) that fits into the given number of bytes. */
    public static function maxMegapixelsFor(int $bytes, int $frames = 1): float
    {
        // Original + a variant of up to ~4 MP (1920 px wide) on top.
        $pixels = ($bytes / (self::BYTES_PER_PIXEL * self::OVERHEAD)) - 1920 * 1920;

        return max(0, round($pixels / $frames / 1_000_000, 1));
    }

    private function orientation(string $binary): int
    {
        if (! function_exists('exif_read_data')) {
            return 1;
        }
        $stream = fopen('php://memory', 'r+b');
        fwrite($stream, $binary);
        rewind($stream);
        $exif = @exif_read_data($stream);
        fclose($stream);
        $orientation = (int) ($exif['Orientation'] ?? 1);

        return $orientation >= 1 && $orientation <= 8 ? $orientation : 1;
    }
}
