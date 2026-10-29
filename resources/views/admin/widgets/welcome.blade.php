<x-filament-widgets::widget>
    <div class="lwk-welcome">
        <div class="lwk-welcome-main">
            <h2 class="lwk-welcome-title">{{ $greeting }}</h2>
            <p class="lwk-muted">{{ __('larawebkit-dashboard.welcome.subtitle', ['site' => setting('general.site_name')]) }}</p>
            <div class="lwk-welcome-actions">
                @foreach ($actions as $action)
                    <x-filament::button tag="a" :href="$action['url']" :icon="$action['icon']" color="gray" size="sm">{{ $action['label'] }}</x-filament::button>
                @endforeach
                <x-filament::button tag="a" :href="url('/')" target="_blank" icon="heroicon-o-arrow-top-right-on-square" size="sm">{{ __('larawebkit-dashboard.welcome.view_site') }}</x-filament::button>
            </div>
        </div>
        @if ($checklist !== [])
            <div class="lwk-welcome-checklist">
                <p class="lwk-small" style="font-weight:600">{{ __('larawebkit-dashboard.welcome.checklist') }}</p>
                <ul>
                    @foreach ($checklist as $item)
                        <li @class(['is-done' => $item['done']])>
                            <span aria-hidden="true">{{ $item['done'] ? '✓' : '○' }}</span>
                            @if ($item['url'] && ! $item['done'])<a href="{{ $item['url'] }}">{{ $item['label'] }}</a>@else<span>{{ $item['label'] }}</span>@endif
                            <span class="lwk-sr-only">{{ $item['done'] ? __('larawebkit-dashboard.welcome.done') : __('larawebkit-dashboard.welcome.todo') }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</x-filament-widgets::widget>
