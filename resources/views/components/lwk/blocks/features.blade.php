@php($items = array_values(array_filter((array) ($data['items'] ?? []), fn ($i) => filled($i['title'] ?? null))))
@php($image = $media[$data['image'] ?? 0] ?? null)
<div class="lwk-container">
    @include('components.lwk.blocks.partials.head', ['center' => in_array($variant, ['grid', 'cards'], true)])
    <div @class(['lwk-features', 'has-image' => $variant === 'list' && $image])>
        @if ($variant === 'list' && $image)
            <div class="lwk-features-media"><x-lwk.image :media="$image" sizes="(min-width: 960px) 40vw, 100vw" /></div>
        @endif
        <ul class="lwk-feature-list" role="list" style="--cols: {{ count($items) % 4 === 0 && count($items) > 0 ? 4 : max(1, min(3, count($items))) }}">
            @foreach ($items as $item)
                <li class="lwk-feature">
                    @if ($icon = \App\Modules\Blocks\BlockIcons::render($item['icon'] ?? null))<span class="lwk-feature-icon">{{ $icon }}</span>@endif
                    <div>
                        <h3 class="lwk-feature-title">{{ $item['title'] }}</h3>
                        @if (filled($item['text'] ?? null))<p>{{ $item['text'] }}</p>@endif
                        @if (filled($item['url'] ?? null))<a href="{{ $item['url'] }}" class="lwk-more">{{ $item['link_label'] ?: __('larawebkit-front.read_more') }} <span class="lwk-sr-only">: {{ $item['title'] }}</span><span aria-hidden="true">→</span></a>@endif
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</div>
