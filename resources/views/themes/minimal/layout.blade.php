<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme-name="{{ app(\App\Modules\Themes\ThemeManager::class)->active() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-lwk.seo :seo="$seo" />
    @stack('head')
    <script>(function(){try{var t=localStorage.getItem('lwk-theme');if(t==='dark'||(!t&&matchMedia('(prefers-color-scheme: dark)').matches))document.documentElement.dataset.theme='dark';}catch(e){}})();</script>
    @themeStyles
    <link rel="icon" href="{{ asset('favicon.ico') }}">
</head>
<body class="lwk-body">
    <a href="#main" class="lwk-skip">{{ __('larawebkit-front.skip') }}</a>
    @section('header')
        <header class="lwk-header">
            <div class="lwk-container lwk-header-inner">
                <a href="{{ localized_path('/') }}" class="lwk-brand" rel="home">{{ setting('general.site_name') }}</a>
                <x-lwk.menu location="header" />
                <div class="lwk-header-tools">
                    <form action="{{ localized_path('/search') }}" method="GET" role="search" class="lwk-search">
                        <label for="lwk-q" class="lwk-sr-only">{{ __('larawebkit-front.search') }}</label>
                        <input id="lwk-q" type="search" name="q" placeholder="{{ __('larawebkit-front.search_placeholder') }}">
                    </form>
                    <button type="button" class="lwk-theme-toggle" aria-label="{{ __('larawebkit-front.theme_toggle') }}" onclick="var d=document.documentElement;var n=d.dataset.theme==='dark'?'light':'dark';d.dataset.theme=n;try{localStorage.setItem('lwk-theme',n)}catch(e){}">◐</button>
                </div>
            </div>
        </header>
    @show

    @if (! empty($preview))
        <div class="lwk-preview-bar" role="status">{{ __('larawebkit-front.preview') }}</div>
    @endif

    <main id="main" tabindex="-1">
        @yield('content')
    </main>

    @section('footer')
        <footer class="lwk-footer">
            <div class="lwk-container lwk-footer-inner">
                <x-lwk.newsletter />
                <x-lwk.menu location="footer" :label="__('larawebkit-front.footer_menu')" />
                <p class="lwk-meta">
                    © {{ date('Y') }} {{ setting('general.site_name') }}
                    · <a href="{{ url('/feed.xml') }}">{{ __('larawebkit-front.rss') }}</a>
                    @if (setting('privacy.consent_enabled', true)) · <a href="#" data-open-consent>{{ __('larawebkit-privacy.banner.settings') }}</a>@endif
                    @foreach (array_filter((array) setting('social.links', [])) as $network => $link)
                        · <a href="{{ $link }}" rel="me noopener" target="_blank">{{ $network }}</a>
                    @endforeach
                </p>
            </div>
        </footer>
    @show
    <x-lwk.consent />
</body>
</html>
