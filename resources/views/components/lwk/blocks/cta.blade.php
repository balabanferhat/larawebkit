@php($image = $media[$data['image'] ?? 0] ?? null)
<div class="lwk-container">
    <div @class(['lwk-cta', 'has-image' => $variant === 'split' && $image])>
        <div class="lwk-cta-copy">
            @if (filled($data['heading'] ?? null))<h2 class="lwk-section-heading">{{ $data['heading'] }}</h2>@endif
            @if (filled($data['text'] ?? null))<p class="lwk-section-intro">{{ $data['text'] }}</p>@endif
            @include('components.lwk.blocks.partials.buttons')
        </div>
        @if ($variant === 'split' && $image)
            <div class="lwk-cta-media"><x-lwk.image :media="$image" sizes="(min-width: 960px) 40vw, 100vw" /></div>
        @endif
    </div>
</div>
