{{-- One event in a listing. --}}
@php($start = $event->localStart())
<article class="lwk-card lwk-event-card">
    @if ($event->featuredMedia)
        <a href="{{ $event->publicPath() }}" tabindex="-1" aria-hidden="true" class="lwk-card-media">
            <x-lwk.image :media="$event->featuredMedia" :size="640" sizes="(min-width: 960px) 33vw, 100vw" alt="" />
        </a>
    @endif
    <div class="lwk-card-body">
        <p class="lwk-card-kicker"><time datetime="{{ $start->toIso8601String() }}">{{ $start->isoFormat('ll') }}</time> · {{ __('larawebkit-events.location.'.$event->location_type) }}</p>
        <h{{ $level ?? 2 }} class="lwk-card-title"><a href="{{ $event->publicPath() }}">{{ $event->title }}</a></h{{ $level ?? 2 }}>
        @if (filled($event->summary))<p class="lwk-card-excerpt">{{ \Illuminate\Support\Str::limit($event->summary, 150) }}</p>@endif
        @if ($event->status === 'cancelled')<p class="lwk-badge">{{ __('larawebkit-events.status.cancelled') }}</p>@endif
    </div>
</article>
