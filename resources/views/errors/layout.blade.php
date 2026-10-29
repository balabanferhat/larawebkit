{{--
    Error and maintenance pages (500, 503, 429, 419, 403). Self-contained on purpose: they must
    render when the database, the cache or a theme view is broken, so every lookup is guarded
    and the active theme's prebuilt CSS is inlined instead of linked.
--}}
@php
    $safe = function (callable $fn, $default = null) { try { return $fn(); } catch (\Throwable) { return $default; } };
    $siteName = $safe(fn () => setting('general.site_name'), config('app.name')) ?: config('app.name');
    $themes = $safe(fn () => app(\App\Modules\Themes\ThemeManager::class));
    $css = '';
    foreach ($safe(fn () => $themes->chain(), []) as $theme) {
        if (is_file($theme->assetPath('theme.css'))) { $css = (string) file_get_contents($theme->assetPath('theme.css')); break; }
    }
    if ($css === '' && is_file(base_path('themes/minimal/assets/theme.css'))) { $css = (string) file_get_contents(base_path('themes/minimal/assets/theme.css')); }
    $tokens = $safe(fn () => app(\App\Modules\Themes\Design\DesignTokens::class)->css(), '');
    $scheme = $safe(fn () => app(\App\Modules\Themes\Design\DesignTokens::class)->values()['color_scheme'], 'system');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') – {{ $siteName }}</title>
    <script>(function(){var d=document.documentElement,s=@js($scheme),t=null;try{t=localStorage.getItem('lwk-theme')}catch(e){}if(t!=='light'&&t!=='dark'){t=s==='system'?(matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light'):s}d.dataset.theme=t})();</script>
    <style>{!! $tokens !!}{!! $css !!}</style>
</head>
<body class="lwk-body">
    <header class="lwk-header">
        <div class="lwk-container lwk-header-inner">
            <a href="{{ url('/') }}" class="lwk-brand" rel="home"><span class="lwk-brand-name">{{ $siteName }}</span></a>
        </div>
    </header>
    <main id="main" class="lwk-container lwk-system">
        <p class="lwk-system-code" aria-hidden="true">@yield('code')</p>
        <h1>@yield('title')</h1>
        <p class="lwk-lead">@yield('message')</p>
        @yield('extra')
    </main>
    <footer class="lwk-footer"><div class="lwk-container lwk-footer-bottom"><p>© {{ date('Y') }} {{ $siteName }}</p></div></footer>
</body>
</html>
