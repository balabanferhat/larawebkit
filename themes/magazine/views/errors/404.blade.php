@extends(app(\App\Modules\Themes\ThemeManager::class)->view('layout'))

{{-- Magazine 404 / 410: the message and a search, then the "not_found" block preset (latest articles). --}}
@php($gone = ($status ?? 404) === 410)

@section('content')
    <div class="lwk-container lwk-system">
        <p class="lwk-system-code" aria-hidden="true">{{ $gone ? '410' : '404' }}</p>
        <h1>{{ $gone ? __('larawebkit-front.gone_text') : __('larawebkit-front.not_found_title') }}</h1>
        <p class="lwk-lead">{{ $gone ? __('larawebkit-front.gone_hint') : __('larawebkit-front.not_found_text') }}</p>
        <form action="{{ localized_path('/search') }}" method="GET" role="search" class="lwk-inline-form">
            <label for="q404" class="lwk-sr-only">{{ __('larawebkit-front.search') }}</label>
            <input id="q404" type="search" name="q" placeholder="{{ __('larawebkit-front.search_placeholder') }}">
            <button type="submit">{{ __('larawebkit-front.search') }}</button>
        </form>
        <p class="lwk-buttons lwk-system-actions"><a href="{{ localized_path('/') }}" class="lwk-button">{{ __('larawebkit-front.back_home') }}</a></p>
    </div>
    @if (\App\Modules\Core\ModuleRegistry::enabled('content'))
        <x-lwk.blocks :blocks="\App\Modules\Blocks\BlockPresets::blocks('not_found')" />
    @endif
@endsection
