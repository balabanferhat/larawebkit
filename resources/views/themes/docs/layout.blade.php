@extends('themes.minimal.layout')

@section('header')
    <header class="lwk-header">
        <div class="lwk-container lwk-header-inner">
            <a href="{{ localized_path('/') }}" class="lwk-brand" rel="home">{{ setting('general.site_name') }}</a>
            <x-lwk.menu location="header" />
            <form action="{{ localized_path('/search') }}" method="GET" role="search" class="lwk-search">
                <label for="lwk-q" class="lwk-sr-only">{{ __('larawebkit-front.search') }}</label>
                <input id="lwk-q" type="search" name="q" placeholder="{{ __('larawebkit-front.search_placeholder') }}">
            </form>
            <button type="button" class="lwk-theme-toggle" aria-label="{{ __('larawebkit-front.theme_toggle') }}" onclick="var d=document.documentElement;var n=d.dataset.theme==='dark'?'light':'dark';d.dataset.theme=n;try{localStorage.setItem('lwk-theme',n)}catch(e){}">◐</button>
        </div>
    </header>
@endsection
