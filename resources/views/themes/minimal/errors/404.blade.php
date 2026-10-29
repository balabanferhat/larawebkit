@extends(app(\App\Modules\Themes\ThemeManager::class)->view('layout'))

@section('content')
    <div class="lwk-container lwk-error-page">
        <h1>{{ ($status ?? 404) === 410 ? __('larawebkit-front.gone_text') : __('larawebkit-front.not_found_title') }}</h1>
        <p>{{ ($status ?? 404) === 410 ? '' : __('larawebkit-front.not_found_text') }}</p>
        <form action="{{ localized_path('/search') }}" method="GET" role="search" class="lwk-inline-form">
            <label for="q404" class="lwk-sr-only">{{ __('larawebkit-front.search') }}</label>
            <input id="q404" type="search" name="q" placeholder="{{ __('larawebkit-front.search_placeholder') }}">
            <button type="submit">{{ __('larawebkit-front.search') }}</button>
        </form>
        <p><a href="{{ localized_path('/') }}">{{ __('larawebkit-front.back_home') }}</a></p>
    </div>
@endsection
