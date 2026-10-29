@php($md = fn ($v) => str_replace(['\\', '`', '*', '_', '[', ']', '(', ')', '#', '!', '|'], ['\\\\', '\`', '\*', '\_', '\[', '\]', '\(', '\)', '\#', '\!', '\|'], e((string) $v)))
@php($start = $event->localStart())
<x-mail::message>
{!! __($link ? 'larawebkit-events.mail.link_intro' : 'larawebkit-events.mail.confirmation_intro', ['name' => $md($registration->name), 'event' => $md($event->title)]) !!}

<x-mail::panel>
**{{ __('larawebkit-events.when') }}:** {{ $start->isoFormat('LLLL') }} ({{ $event->timezone }})<br>
@if ($event->hasVenue())
**{{ __('larawebkit-events.where') }}:** {!! $md(trim($event->venue_name.', '.preg_replace('/\R+/', ', ', (string) $event->address), ', ')) !!}<br>
@endif
@if ($event->isOnline())
**{{ __('larawebkit-events.where') }}:** {{ __('larawebkit-events.location.online') }}
@endif
</x-mail::panel>

@if ($online !== '')
<x-mail::button :url="$online">
{{ __('larawebkit-events.registration.join') }}
</x-mail::button>
@elseif ($event->isOnline())
{{ __('larawebkit-events.registration.link_later') }}
@endif

[{{ __('larawebkit-events.mail.manage') }}]({{ $manage }})

{{ __('larawebkit-events.mail.calendar_attached') }}

{{ setting('general.site_name') }}
</x-mail::message>
