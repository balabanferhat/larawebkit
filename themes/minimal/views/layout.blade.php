<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme-name="{{ app(\App\Modules\Themes\ThemeManager::class)->active() }}" data-l-prev="{{ __('larawebkit-front.previous') }}" data-l-next="{{ __('larawebkit-front.next') }}" data-l-close="{{ __('larawebkit-front.close') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-lwk.seo :seo="$seo" />
    @stack('head')
    <x-lwk.head />
</head>
<body class="lwk-body @yield('body_class')">
    <a href="#main" class="lwk-skip">{{ __('larawebkit-front.skip') }}</a>
    @section('header')
        @include('theme::partials.header')
    @show

    @if (! empty($preview))
        <div class="lwk-preview-bar" role="status">{{ __('larawebkit-front.preview') }}</div>
    @endif

    <main id="main" tabindex="-1" class="@yield('main_class')">
        @yield('content')
    </main>

    @section('footer')
        @include('theme::partials.footer')
    @show
    <x-lwk.consent />
</body>
</html>
