@php($buttons = array_values(array_filter((array) ($data['buttons'] ?? []), fn ($b) => filled($b['label'] ?? null) && filled($b['url'] ?? null))))
@if ($buttons !== [])
    <p class="lwk-buttons">
        @foreach ($buttons as $button)
            <a href="{{ $button['url'] }}" @class(['lwk-button', 'lwk-button-secondary' => ($button['style'] ?? '') === 'secondary', 'lwk-button-link' => ($button['style'] ?? '') === 'link'])>{{ $button['label'] }}@if (($button['style'] ?? '') === 'link') <span aria-hidden="true">→</span>@endif</a>
        @endforeach
    </p>
@endif
