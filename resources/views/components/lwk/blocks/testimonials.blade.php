@php($items = array_values(array_filter((array) ($data['items'] ?? []), fn ($i) => filled($i['quote'] ?? null))))
<div class="lwk-container">
    @include('components.lwk.blocks.partials.head', ['center' => true])
    <ul class="lwk-quotes" role="list" @if ($variant === 'scroll') tabindex="0" aria-label="{{ $data['heading'] ?? __('larawebkit-blocks.types.testimonials') }}" @endif>
        @foreach ($variant === 'single' ? array_slice($items, 0, 1) : $items as $item)
            @php($photo = $media[$item['photo'] ?? 0] ?? null)
            <li>
                <figure class="lwk-quote">
                    @if (! empty($item['rating']))<p class="lwk-rating" aria-label="{{ (int) $item['rating'] }}/5">{{ str_repeat('★', (int) $item['rating']) }}</p>@endif
                    <blockquote><p>{{ $item['quote'] }}</p></blockquote>
                    <figcaption>
                        @if ($photo)<x-lwk.image :media="$photo" size="thumb" class="lwk-avatar" alt="" />@endif
                        <span><strong>{{ $item['name'] ?? '' }}</strong>@if (filled($item['role'] ?? null))<span class="lwk-meta">{{ $item['role'] }}</span>@endif</span>
                    </figcaption>
                </figure>
            </li>
        @endforeach
    </ul>
</div>
