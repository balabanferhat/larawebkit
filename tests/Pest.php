<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Feature');
pest()->extend(TestCase::class)->in('Unit');

function superAdmin(array $attributes = []): User
{
    return User::factory()->superAdmin()->create($attributes);
}

function editor(array $attributes = []): User
{
    return User::factory()->editor()->create($attributes);
}

function author(array $attributes = []): User
{
    return User::factory()->author()->create($attributes);
}

/**
 * A real JPEG generated with GD. Kept small (and cached per size) because decoded
 * bitmaps cost ~4 bytes per pixel and, on PHP builds with bundled GD (Windows),
 * that memory counts towards memory_limit.
 */
function fakeJpeg(int $width = 1200, int $height = 800): string
{
    static $cache = [];

    return $cache["{$width}x{$height}"] ??= (function () use ($width, $height) {
        $img = imagecreatetruecolor($width, $height);
        imagefilledrectangle($img, 0, 0, $width, $height, imagecolorallocate($img, 30, 120, 200));
        imagefilledellipse($img, (int) ($width / 2), (int) ($height / 2), (int) ($width / 3), (int) ($height / 3), imagecolorallocate($img, 250, 200, 0));
        ob_start();
        imagejpeg($img, null, 85);
        unset($img);

        return (string) ob_get_clean();
    })();
}

/**
 * A tiny valid JPEG whose header claims the given size: getimagesize() reports it, but
 * decoding it would need width × height × 4 bytes. Used to test the input guards
 * without allocating a huge bitmap.
 */
function jpegClaimingSize(int $width, int $height): string
{
    $jpeg = fakeJpeg(16, 16);
    $sof = strpos($jpeg, "\xFF\xC0");

    return substr_replace($jpeg, pack('n', $height).pack('n', $width), $sof + 5, 4);
}

/** A JPEG with an EXIF APP1 segment carrying the given orientation tag. */
function jpegWithOrientation(int $width, int $height, int $orientation): string
{
    $jpeg = fakeJpeg($width, $height);
    $tiff = "MM\x00\x2a\x00\x00\x00\x08"."\x00\x01"."\x01\x12\x00\x03\x00\x00\x00\x01".pack('n', $orientation)."\x00\x00"."\x00\x00\x00\x00";
    $exif = "Exif\x00\x00".$tiff;

    return substr($jpeg, 0, 2)."\xFF\xE1".pack('n', strlen($exif) + 2).$exif.substr($jpeg, 2);
}
