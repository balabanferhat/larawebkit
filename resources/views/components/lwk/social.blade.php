@php($links = array_filter((array) setting('social.links', []), fn ($url) => filled($url)))
@if ($links !== [])
    <ul {{ $attributes->merge(['class' => 'lwk-social']) }}>
        @foreach ($links as $network => $url)
            <li><a href="{{ $url }}" rel="me noopener" target="_blank" aria-label="{{ __('larawebkit-front.social.profile', ['network' => \Illuminate\Support\Str::headline($network)]) }}">{{ \App\Modules\Themes\Support\Icons::social($network, $url) }}</a></li>
        @endforeach
    </ul>
@endif
