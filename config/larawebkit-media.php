<?php

/*
|--------------------------------------------------------------------------
| Media library and image pipeline
|--------------------------------------------------------------------------
|
| Every image (upload, Pexels, import) is converted to WebP and resized into
| width variants (never upscaled). EXIF/GPS metadata is stripped. The original
| is kept on a private disk so `php artisan larawebkit:media:regenerate` can
| rebuild the variants after you change these values.
|
*/

return [

    // gd | imagick | auto (prefers Imagick when it can write WebP, else GD)
    'driver' => env('MEDIA_IMAGE_DRIVER', 'auto'),

    'disk' => env('MEDIA_DISK', 'public'),
    'directory' => 'media',

    'original_disk' => env('MEDIA_ORIGINAL_DISK', 'local'),
    'original_directory' => 'media-originals',

    'widths' => [320, 640, 960, 1280, 1920],

    // Square crop used by the library grid and the "thumbnail" insert size.
    'thumbnail' => 300,

    'quality' => (int) env('MEDIA_QUALITY', 80),

    // Insert sizes offered by the editor's media modal => variant width.
    'sizes' => [
        'thumbnail' => 'thumb',
        'medium' => 640,
        'large' => 1280,
        'full' => 'max',
    ],

    'max_upload_kb' => (int) env('MEDIA_MAX_UPLOAD_KB', 10240),

    'accepted_mime_types' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],

    // Variants generated for stock photos (Pexels).
    'stock_widths' => [800, 1600],

    // Open Graph image (1200×630). WebP by default (all images WebP); set to "jpeg"
    // if you need previews in apps that still do not render WebP.
    'og_format' => env('MEDIA_OG_FORMAT', 'webp'),

    // Default `sizes` attribute for responsive images.
    'default_sizes_attribute' => '(min-width: 1024px) 768px, 100vw',

];
