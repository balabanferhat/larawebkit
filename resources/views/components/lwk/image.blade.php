@props(['media', 'alt' => null, 'sizes' => null, 'priority' => false, 'size' => 'max'])
@if ($media)
    {{ \App\Modules\Media\Support\ResponsiveImage::tag($media, $alt, $sizes, $priority, $attributes->getAttributes(), $size) }}
@endif
