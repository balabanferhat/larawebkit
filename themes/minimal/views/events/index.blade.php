@extends(app(\App\Modules\Themes\ThemeManager::class)->view('layout'))

@section('content')
    <div class="lwk-container lwk-listing lwk-events">
        <header class="lwk-listing-head">
            <x-lwk.breadcrumbs :seo="$seo" />
            <h1>{{ __('larawebkit-events.plural') }}</h1>
        </header>
        <section aria-labelledby="upcoming">
            <h2 id="upcoming" class="lwk-section-heading">{{ __('larawebkit-events.upcoming') }}</h2>
            @if ($upcoming->isEmpty())
                <div class="lwk-empty"><p>{{ __('larawebkit-events.none') }}</p></div>
            @else
                <div class="lwk-cards">
                    @foreach ($upcoming as $event)
                        @include('theme::events._card', ['event' => $event, 'level' => 3])
                    @endforeach
                </div>
                @include('theme::partials.pagination', ['posts' => $upcoming])
            @endif
        </section>
        @if ($past->isNotEmpty())
            <section aria-labelledby="past" class="lwk-events-past">
                <h2 id="past" class="lwk-section-heading">{{ __('larawebkit-events.past') }}</h2>
                <ul class="lwk-post-list" role="list">
                    @foreach ($past as $event)
                        <li><a href="{{ $event->publicPath() }}">{{ $event->title }}</a> <span class="lwk-meta">· {{ $event->localStart()->isoFormat('ll') }}</span></li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
@endsection
