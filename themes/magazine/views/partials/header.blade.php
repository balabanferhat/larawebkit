{{-- Magazine: a slim top bar (date, social), a centred masthead and the section navigation below it. --}}
<x-lwk.announcement />
<div class="lwk-topbar" role="complementary" aria-label="{{ __('theme-magazine::theme.topbar') }}">
    <div class="lwk-container lwk-topbar-inner">
        <p><time datetime="{{ now()->toDateString() }}">{{ now()->isoFormat('dddd, LL') }}</time></p>
        <x-lwk.social />
    </div>
</div>
<header class="lwk-header lwk-masthead">
    <div class="lwk-container lwk-header-inner">
        <div class="lwk-masthead-side">
            @if (setting('header.search', true))
                <form action="{{ localized_path('/search') }}" method="GET" role="search" class="lwk-search">
                    <label for="lwk-q" class="lwk-sr-only">{{ __('larawebkit-front.search') }}</label>
                    <input id="lwk-q" type="search" name="q" placeholder="{{ __('larawebkit-front.search_placeholder') }}">
                    <span class="lwk-search-icon">{{ \App\Modules\Themes\Support\Icons::ui('magnifying-glass') }}</span>
                </form>
            @endif
        </div>
        <x-lwk.brand />
        <div class="lwk-masthead-side lwk-masthead-end">
            @if (setting('appearance.theme_toggle', true))
                <button type="button" class="lwk-theme-toggle" data-theme-toggle aria-label="{{ __('larawebkit-front.theme_toggle') }}">
                    <span class="lwk-when-light">{{ \App\Modules\Themes\Support\Icons::ui('moon') }}</span>
                    <span class="lwk-when-dark">{{ \App\Modules\Themes\Support\Icons::ui('sun') }}</span>
                </button>
            @endif
            @if (class_exists(\App\Modules\Members\Members::class) && \App\Modules\Members\Members::enabled() && setting('members.header_link', true))
                @php($member = \App\Modules\Members\Members::current())
                <a href="{{ $member ? route('members.account') : route('members.login') }}" class="lwk-header-account">{{ $member ? __('larawebkit-members.account.title') : __('larawebkit-members.login.title') }}</a>
            @endif
            @if (filled(setting('header.cta_label')) && filled(setting('header.cta_url')))
                <a href="{{ setting('header.cta_url') }}" class="lwk-button lwk-header-cta">{{ setting('header.cta_label') }}</a>
            @endif
            <button type="button" class="lwk-nav-toggle" aria-expanded="false" aria-controls="lwk-header-panel" data-nav-toggle>
                <span class="lwk-nav-toggle-open">{{ \App\Modules\Themes\Support\Icons::ui('o-bars-3') }}</span>
                <span class="lwk-nav-toggle-close">{{ \App\Modules\Themes\Support\Icons::ui('o-x-mark') }}</span>
                <span class="lwk-sr-only">{{ __('larawebkit-front.nav.toggle') }}</span>
            </button>
        </div>
    </div>
    <div class="lwk-header-panel lwk-sections" id="lwk-header-panel">
        <div class="lwk-container">
            <x-lwk.menu location="header" :label="__('larawebkit-front.menu')" />
        </div>
    </div>
</header>
