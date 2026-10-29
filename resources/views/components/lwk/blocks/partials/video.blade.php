{{-- Click-to-load video: our own poster image, nothing requested from the host until play. --}}
@php($provider = $video['provider'] === 'youtube' ? 'YouTube' : 'Vimeo')
<div class="lwk-video">
    <a href="{{ $video['url'] }}" class="lwk-video-poster" data-video-src="{{ $video['embed'] }}" data-video-title="{{ $title }}" target="_blank" rel="noopener">
        @if ($poster)
            <x-lwk.image :media="$poster" alt="" sizes="(min-width: 960px) 50vw, 100vw" :priority="$priority ?? false" />
        @endif
        <span class="lwk-video-play">{{ \App\Modules\Themes\Support\Icons::ui('s-play') }}<span class="lwk-sr-only">{{ __('larawebkit-blocks.video.play', ['title' => $title ?: $provider]) }}</span></span>
    </a>
    <p class="lwk-video-notice">{{ __('larawebkit-blocks.video.notice', ['provider' => $provider]) }}</p>
</div>
