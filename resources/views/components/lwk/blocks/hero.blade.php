@php
    $image = $media[$data['image'] ?? 0] ?? null;
    $video = $variant === 'video' ? \App\Modules\Blocks\VideoEmbed::parse((string) ($data['video_url'] ?? '')) : null;
    $level = $first ? 1 : 2;
@endphp
@if ($first && $image && $variant !== 'video')
    @push('head'){{ \App\Modules\Media\Support\ResponsiveImage::preload($image, $variant === 'background' ? '100vw' : '(min-width: 960px) 50vw, 100vw') }}@endpush
@endif
@if ($variant === 'background' && $image)
    <div class="lwk-hero-media" aria-hidden="true"><x-lwk.image :media="$image" alt="" sizes="100vw" :priority="$first" /></div>
@endif
<div class="lwk-container lwk-hero-inner">
    <div class="lwk-hero-copy">
        @if (filled($data['eyebrow'] ?? null))<p class="lwk-eyebrow">{{ $data['eyebrow'] }}</p>@endif
        <h{{ $level }} class="lwk-hero-title">{{ $data['heading'] ?? '' }}</h{{ $level }}>
        @if (filled($data['text'] ?? null))<p class="lwk-hero-text">{{ $data['text'] }}</p>@endif
        @include('components.lwk.blocks.partials.buttons')
        @if (filled($data['note'] ?? null))<p class="lwk-hero-note">{{ $data['note'] }}</p>@endif
    </div>
    @if ($variant === 'video' && $video)
        <div class="lwk-hero-visual">@include('components.lwk.blocks.partials.video', ['video' => $video, 'poster' => $image, 'title' => $data['heading'] ?? '', 'priority' => $first])</div>
    @elseif ($image && in_array($variant, ['split', 'portrait', 'centered'], true))
        <div class="lwk-hero-visual">
            <x-lwk.image :media="$image" :sizes="$variant === 'centered' ? '(min-width: 72rem) 72rem, 100vw' : '(min-width: 960px) 50vw, 100vw'" :priority="$first" />
        </div>
    @endif
</div>
