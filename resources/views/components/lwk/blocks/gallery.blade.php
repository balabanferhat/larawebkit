@php($images = collect((array) ($data['images'] ?? []))->map(fn ($id) => $media[$id] ?? null)->filter()->values())
@php($cols = max(2, min(4, (int) ($data['columns'] ?? 3))))
<div class="lwk-container">
    @include('components.lwk.blocks.partials.head')
    <ul class="lwk-gallery" role="list" style="--cols: {{ $cols }}" data-gallery>
        @foreach ($images as $i => $image)
            <li>
                <a href="{{ $image->url(1920) }}" data-lightbox data-alt="{{ $image->alt }}" data-caption="{{ $image->caption }}" aria-label="{{ $image->alt ?: __('larawebkit-blocks.gallery.open', ['n' => $i + 1]) }}">
                    <x-lwk.image :media="$image" size="640" :sizes="'(min-width: 960px) '.round(100 / $cols).'vw, 50vw'" />
                </a>
                @if ($image->caption)<p class="lwk-meta">{{ $image->caption }}</p>@endif
            </li>
        @endforeach
    </ul>
</div>
