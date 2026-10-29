{{-- Renders a page's section blocks. Each block view gets $data, $variant, $media (preloaded), $first, $index and the block's prepare() data. --}}
@props(['blocks'])
@php
    $registry = app(\App\Modules\Blocks\BlockRegistry::class);
    $list = $registry->normalize($blocks);
    $mediaIds = $registry->mediaIds($list);
    $media = $mediaIds ? \App\Modules\Media\Models\Media::whereIn('id', $mediaIds)->get()->keyBy('id') : collect();
@endphp
@foreach ($list as $index => $block)
    @php
        $class = $registry->get($block['type']);
        $data = $block['data'];
        $variant = in_array($data['variant'] ?? null, $class::variants(), true) ? $data['variant'] : $class::variants()[0];
        $background = $class::hasBackground() && in_array($data['background'] ?? 'plain', \App\Modules\Blocks\BlockFields::BACKGROUNDS, true) ? ($data['background'] ?? 'plain') : 'plain';
        $bgImage = $background === 'image' ? ($media[$data['background_image'] ?? 0] ?? null) : null;
        $spacing = in_array($data['spacing'] ?? 'normal', \App\Modules\Blocks\BlockFields::SPACING, true) ? ($data['spacing'] ?? 'normal') : 'normal';
        $anchor = filled($data['anchor'] ?? null) ? \Illuminate\Support\Str::slug($data['anchor']) : null;
    @endphp
    <section @if ($anchor) id="{{ $anchor }}" @endif @class([
        'lwk-block', 'lwk-block-'.str_replace('_', '-', $block['type']), 'lwk-v-'.$variant, 'lwk-bg-'.($bgImage ? 'image' : ($background === 'image' ? 'plain' : $background)),
        'lwk-space-'.$spacing, 'lwk-hide-mobile' => ! empty($data['hide_on_mobile']), 'is-first' => $index === 0,
    ])>
        @if ($bgImage)
            <div class="lwk-block-bg" aria-hidden="true"><x-lwk.image :media="$bgImage" alt="" sizes="100vw" :priority="$index === 0" /></div>
        @endif
        @include($registry->view($block['type']), ['data' => $data, 'variant' => $variant, 'media' => $media, 'first' => $index === 0, 'index' => $index, 'blockId' => 'b'.$index] + $class::prepare($data))
    </section>
@endforeach
