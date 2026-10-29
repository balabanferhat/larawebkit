{{-- When and where, shared by the event page and the registration page. --}}
@php($start = $event->localStart())
@php($end = $event->localEnd())
<dl class="lwk-event-facts">
    <div>
        <dt>{{ __('larawebkit-events.when') }}</dt>
        <dd>
            <time datetime="{{ $start->toIso8601String() }}">{{ $start->isoFormat('LLLL') }}</time>
            @if ($end) – <time datetime="{{ $end->toIso8601String() }}">{{ $end->isSameDay($start) ? $end->isoFormat('LT') : $end->isoFormat('LLLL') }}</time>@endif
            <span class="lwk-meta">({{ $event->timezone }})</span>
        </dd>
    </div>
    <div>
        <dt>{{ __('larawebkit-events.where') }}</dt>
        <dd>
            @if ($event->hasVenue())
                @if ($event->venue_name)<strong>{{ $event->venue_name }}</strong><br>@endif
                @if ($event->address){!! nl2br(e($event->address)) !!}<br>@endif
            @endif
            @if ($event->isOnline()){{ __('larawebkit-events.location.online') }}@endif
        </dd>
    </div>
    @if ($event->capacity && $event->status === 'published' && ! $event->isPast())
        <div>
            <dt class="lwk-sr-only">{{ __('larawebkit-events.fields.capacity') }}</dt>
            <dd>{{ $event->isFull() ? __('larawebkit-events.full') : trans_choice('larawebkit-events.spots_left', $event->spotsLeft(), ['count' => $event->spotsLeft()]) }}</dd>
        </div>
    @endif
</dl>
