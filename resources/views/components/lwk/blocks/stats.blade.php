@php($items = array_values(array_filter((array) ($data['items'] ?? []), fn ($i) => filled($i['value'] ?? null))))
<div class="lwk-container">
    @include('components.lwk.blocks.partials.head', ['center' => true])
    <dl class="lwk-stats">
        @foreach ($items as $item)
            <div class="lwk-stat"><dt>{{ $item['label'] ?? '' }}</dt><dd>{{ $item['value'] }}</dd></div>
        @endforeach
    </dl>
</div>
