@php($plans = array_values(array_filter((array) ($data['plans'] ?? []), fn ($p) => filled($p['name'] ?? null))))
<div class="lwk-container">
    @include('components.lwk.blocks.partials.head', ['center' => true])
    <ul class="lwk-plans" role="list" style="--cols: {{ max(1, min(4, count($plans))) }}">
        @foreach ($plans as $plan)
            <li @class(['lwk-plan', 'is-highlighted' => ! empty($plan['highlighted'])])>
                <div class="lwk-plan-head">
                    <h3>{{ $plan['name'] }}</h3>
                    @if (filled($plan['badge'] ?? null))<span class="lwk-badge">{{ $plan['badge'] }}</span>@endif
                </div>
                @if (filled($plan['description'] ?? null))<p class="lwk-meta">{{ $plan['description'] }}</p>@endif
                @if (filled($plan['price'] ?? null))<p class="lwk-price"><span>{{ $plan['price'] }}</span>@if (filled($plan['period'] ?? null)) <small>/ {{ $plan['period'] }}</small>@endif</p>@endif
                @php($features = array_filter(array_map('trim', preg_split('/\R/', (string) ($plan['features'] ?? '')))))
                @if ($features)
                    <ul class="lwk-checks" role="list">@foreach ($features as $f)<li>{{ \App\Modules\Themes\Support\Icons::ui('check') }}<span>{{ $f }}</span></li>@endforeach</ul>
                @endif
                @if (filled($plan['button_label'] ?? null) && filled($plan['button_url'] ?? null))
                    <a href="{{ $plan['button_url'] }}" @class(['lwk-button', 'lwk-button-secondary' => empty($plan['highlighted'])])>{{ $plan['button_label'] }}<span class="lwk-sr-only">: {{ $plan['name'] }}</span></a>
                @endif
            </li>
        @endforeach
    </ul>
    @if (filled($data['note'] ?? null))<p class="lwk-meta lwk-center">{{ $data['note'] }}</p>@endif
</div>
