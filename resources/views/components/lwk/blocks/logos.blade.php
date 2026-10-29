@php($logos = array_values(array_filter((array) ($data['logos'] ?? []), fn ($l) => filled($l['name'] ?? null))))
@if ($logos !== [])
<div class="lwk-container">
    @if (filled($data['heading'] ?? null))<h2 class="lwk-logos-title">{{ $data['heading'] }}</h2>@endif
    <ul class="lwk-logos" role="list">
        @foreach ($logos as $logo)
            @php($image = $media[$logo['image'] ?? 0] ?? null)
            <li>
                @if (filled($logo['url'] ?? null))<a href="{{ $logo['url'] }}" rel="noopener" target="_blank">@endif
                @if ($image)<x-lwk.image :media="$image" size="320" sizes="160px" :alt="$logo['name']" />@else<span class="lwk-logo-text">{{ $logo['name'] }}</span>@endif
                @if (filled($logo['url'] ?? null))</a>@endif
            </li>
        @endforeach
    </ul>
</div>
@endif
