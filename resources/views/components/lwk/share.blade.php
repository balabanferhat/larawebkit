{{-- Share links: plain URLs, no third-party scripts or tracking. "Copy link" appears only with JavaScript. --}}
@props(['url', 'title'])
@php($u = rawurlencode($url))
@php($t = rawurlencode($title))
@php($networks = [
    'x' => ['X', "https://x.com/intent/post?url=$u&text=$t"],
    'facebook' => ['Facebook', "https://www.facebook.com/sharer/sharer.php?u=$u"],
    'linkedin' => ['LinkedIn', "https://www.linkedin.com/sharing/share-offsite/?url=$u"],
    'whatsapp' => ['WhatsApp', "https://wa.me/?text=$t%20$u"],
    'telegram' => ['Telegram', "https://t.me/share/url?url=$u&text=$t"],
])
<aside {{ $attributes->merge(['class' => 'lwk-share']) }} aria-labelledby="lwk-share-title">
    <h2 id="lwk-share-title" class="lwk-share-title">{{ __('larawebkit-front.share.title') }}</h2>
    <ul class="lwk-share-list" role="list">
        @foreach ($networks as $key => [$name, $href])
            <li><a href="{{ $href }}" target="_blank" rel="noopener noreferrer nofollow" aria-label="{{ __('larawebkit-front.share.on', ['network' => $name]) }}">{{ $key === 'linkedin' ? \App\Modules\Themes\Support\Icons::ui('o-share') : \App\Modules\Themes\Support\Icons::social($key) }}<span>{{ $name }}</span></a></li>
        @endforeach
        <li><a href="mailto:?subject={{ $t }}&amp;body={{ $u }}">{{ \App\Modules\Themes\Support\Icons::ui('o-envelope') }}<span>{{ __('larawebkit-front.share.email') }}</span></a></li>
        <li hidden data-lwk-share-copy><button type="button" data-url="{{ $url }}" data-copied="{{ __('larawebkit-front.share.copied') }}">{{ \App\Modules\Themes\Support\Icons::ui('o-link') }}<span>{{ __('larawebkit-front.share.copy') }}</span></button></li>
    </ul>
</aside>
