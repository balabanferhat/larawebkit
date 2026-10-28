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

/** A real JPEG with EXIF-free pixels, generated with GD. */
function fakeJpeg(int $width = 2400, int $height = 1600): string
{
    $img = imagecreatetruecolor($width, $height);
    imagefilledrectangle($img, 0, 0, $width, $height, imagecolorallocate($img, 30, 120, 200));
    imagefilledellipse($img, (int) ($width / 2), (int) ($height / 2), (int) ($width / 3), (int) ($height / 3), imagecolorallocate($img, 250, 200, 0));
    ob_start();
    imagejpeg($img, null, 90);

    return (string) ob_get_clean();
}
