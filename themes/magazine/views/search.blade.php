@extends(app(\App\Modules\Themes\ThemeManager::class)->view('layout'))

{{-- Magazine search: the results as in the parent theme, then latest articles when nothing matched and the newsletter. --}}

@section('content')
    <div class="lwk-container lwk-listing lwk-search-page">
        <header class="lwk-listing-head">
            <h1>{{ $q !== '' ? __('larawebkit-front.search_results', ['q' => $q]) : __('larawebkit-front.search') }}</h1>
            <form action="{{ localized_path('/search') }}" method="GET" role="search" class="lwk-inline-form">
                <label for="lwk-search-q" class="lwk-sr-only">{{ __('larawebkit-front.search') }}</label>
                <input id="lwk-search-q" type="search" name="q" value="{{ $q }}" placeholder="{{ __('larawebkit-front.search_placeholder') }}">
                <button type="submit">{{ __('larawebkit-front.search') }}</button>
            </form>
            @if ($q !== '')<p class="lwk-meta" role="status">{{ trans_choice('larawebkit-front.results_count', $posts->count(), ['count' => $posts->count()]) }}</p>@endif
        </header>
        @if ($q !== '')
            @if ($posts->isEmpty())
                <div class="lwk-empty">
                    <p><strong>{{ __('larawebkit-front.no_results') }}</strong></p>
                    <p>{{ __('larawebkit-front.no_results_hint') }}</p>
                </div>
            @else
                @php($needle = '/('.preg_quote(e($q), '/').')/iu')
                <ol class="lwk-results" role="list">
                    @foreach ($posts as $post)
                        <li class="lwk-result">
                            <p class="lwk-meta"><time datetime="{{ $post->published_at?->toDateString() }}">{{ $post->published_at?->isoFormat('LL') }}</time></p>
                            <h2><a href="{{ $post->publicPath() }}">{!! preg_replace($needle, '<mark>$1</mark>', e($post->title)) !!}</a></h2>
                            <p>{!! preg_replace($needle, '<mark>$1</mark>', e(\Illuminate\Support\Str::limit($post->excerpt ?: $post->seoDescription(), 200))) !!}</p>
                        </li>
                    @endforeach
                </ol>
            @endif
        @endif
    </div>
    @if ($q !== '' && $posts->isEmpty())<x-lwk.blocks :blocks="\App\Modules\Blocks\BlockPresets::blocks('not_found')" />@endif
    <x-lwk.blocks :blocks="\App\Modules\Blocks\BlockPresets::blocks('archive')" />
@endsection
