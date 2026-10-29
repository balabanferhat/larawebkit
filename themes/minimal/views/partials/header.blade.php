<x-lwk.announcement />
<header @class(['lwk-header', 'is-sticky' => setting('header.sticky', true)])>
    <div class="lwk-container lwk-header-inner">
        <x-lwk.brand />
        <button type="button" class="lwk-nav-toggle" aria-expanded="false" aria-controls="lwk-header-panel" data-nav-toggle>
            <span class="lwk-nav-toggle-open">{{ \App\Modules\Themes\Support\Icons::ui('o-bars-3') }}</span>
            <span class="lwk-nav-toggle-close">{{ \App\Modules\Themes\Support\Icons::ui('o-x-mark') }}</span>
            <span class="lwk-sr-only">{{ __('larawebkit-front.nav.toggle') }}</span>
        </button>
        <div class="lwk-header-panel" id="lwk-header-panel">
            <x-lwk.menu location="header" :label="__('larawebkit-front.menu')" />
            <div class="lwk-header-tools">
                @if (setting('header.search', true))
                    <form action="{{ localized_path('/search') }}" method="GET" role="search" class="lwk-search">
                        <label for="lwk-q" class="lwk-sr-only">{{ __('larawebkit-front.search') }}</label>
                        <input id="lwk-q" type="search" name="q" placeholder="{{ __('larawebkit-front.search_placeholder') }}">
                        <span class="lwk-search-icon">{{ \App\Modules\Themes\Support\Icons::ui('magnifying-glass') }}</span>
                    </form>
                @endif
                @if (setting('appearance.theme_toggle', true))
                    <button type="button" class="lwk-theme-toggle" data-theme-toggle aria-label="{{ __('larawebkit-front.theme_toggle') }}">
                        <span class="lwk-when-light">{{ \App\Modules\Themes\Support\Icons::ui('moon') }}</span>
                        <span class="lwk-when-dark">{{ \App\Modules\Themes\Support\Icons::ui('sun') }}</span>
                    </button>
                @endif
                @if (filled(setting('header.cta_label')) && filled(setting('header.cta_url')))
                    <a href="{{ setting('header.cta_url') }}" class="lwk-button lwk-header-cta">{{ setting('header.cta_label') }}</a>
                @endif
            </div>
        </div>
    </div>
</header>
