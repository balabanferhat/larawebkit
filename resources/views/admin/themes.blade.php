<x-filament-panels::page>
    <div class="lwk-theme-grid">
        @foreach ($this->getThemes() as $item)
            @php($theme = $item['theme'])
            <article wire:key="theme-{{ $theme->name }}" @class(['lwk-theme-card', 'is-active' => $item['active']])>
                <div class="lwk-theme-shot">
                    @if ($item['screenshot'])
                        <img src="{{ $item['screenshot'] }}" alt="{{ __('larawebkit-themes.manage.screenshot', ['theme' => $theme->label()]) }}" loading="lazy" width="1200" height="900">
                    @else
                        <span>{{ $theme->label() }}</span>
                    @endif
                </div>
                <div class="lwk-theme-body">
                    <div class="lwk-theme-head">
                        <h2>{{ $theme->label() }}</h2>
                        @if ($item['active'])<x-filament::badge color="success">{{ __('larawebkit-themes.manage.active') }}</x-filament::badge>@endif
                    </div>
                    <p class="lwk-muted">{{ $theme->description() }}</p>
                    <p class="lwk-theme-meta">
                        {{ __('larawebkit-themes.manage.version', ['version' => $theme->version()]) }}
                        @if ($theme->author()) · {{ __('larawebkit-themes.manage.author', ['author' => $theme->author()]) }}@endif
                        @if ($item['parent']) · {{ __('larawebkit-themes.manage.child_of', ['parent' => $item['parent']]) }}@endif
                    </p>
                    <div class="lwk-theme-actions">
                        @if ($item['active'])
                            <x-filament::button tag="a" href="{{ \App\Modules\Themes\Filament\Pages\ManageAppearance::getUrl() }}" color="gray" size="sm">{{ __('larawebkit-themes.manage.customize') }}</x-filament::button>
                        @else
                            <x-filament::button wire:click="activate(@js($theme->name))" size="sm">{{ __('larawebkit-themes.manage.activate') }}</x-filament::button>
                        @endif
                        <x-filament::link href="{{ url('/') }}" target="_blank" size="sm">{{ __('larawebkit-themes.manage.view_site') }}</x-filament::link>
                    </div>
                </div>
            </article>
        @endforeach
    </div>
</x-filament-panels::page>
