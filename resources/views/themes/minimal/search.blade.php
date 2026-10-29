@extends(app(\App\Modules\Themes\ThemeManager::class)->view('layout'))

@section('content')
    <div class="lwk-container">
        <h1>{{ $q !== '' ? __('larawebkit-front.search_results', ['q' => $q]) : __('larawebkit-front.search') }}</h1>
        <form action="{{ localized_path('/search') }}" method="GET" role="search" class="lwk-inline-form">
            <label for="q" class="lwk-sr-only">{{ __('larawebkit-front.search') }}</label>
            <input id="q" type="search" name="q" value="{{ $q }}" placeholder="{{ __('larawebkit-front.search_placeholder') }}">
            <button type="submit">{{ __('larawebkit-front.search') }}</button>
        </form>
        @if ($q !== '')
            @forelse ($posts as $post)
                <x-lwk.post-card :post="$post" />
            @empty
                <p>{{ __('larawebkit-front.no_results') }}</p>
            @endforelse
        @endif
    </div>
@endsection
