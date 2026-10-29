{{-- Section heading: eyebrow, heading (h1 for a first hero, else h2) and intro. --}}
@php($level = $level ?? 2)
@if (filled($data['eyebrow'] ?? null) || filled($data['heading'] ?? null) || filled($data[$introKey ?? 'intro'] ?? null))
    <header @class(['lwk-section-head', 'is-center' => $center ?? false])>
        @if (filled($data['eyebrow'] ?? null))<p class="lwk-eyebrow">{{ $data['eyebrow'] }}</p>@endif
        @if (filled($data['heading'] ?? null))<h{{ $level }} class="lwk-section-heading" id="{{ $blockId }}-h">{{ $data['heading'] }}</h{{ $level }}>@endif
        @if (filled($data[$introKey ?? 'intro'] ?? null))<p class="lwk-section-intro">{{ $data[$introKey ?? 'intro'] }}</p>@endif
    </header>
@endif
