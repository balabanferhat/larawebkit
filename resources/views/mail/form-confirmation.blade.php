@php($md = fn ($v) => str_replace(['\\', '`', '*', '_', '[', ']', '(', ')', '#', '!', '|'], ['\\\\', '\`', '\*', '\_', '\[', '\]', '\(', '\)', '\#', '\!', '\|'], e((string) $v)))
<x-mail::message>
{!! nl2br($md($body)) !!}

@if ($download)
<x-mail::button :url="$download">
{{ __('larawebkit-forms.mail.download') }}
</x-mail::button>

{{ __('larawebkit-forms.mail.download_expires', ['hours' => config('larawebkit.download_link_hours', 72)]) }}
@endif

<x-mail::panel>
@foreach ($rows as $label => $value)
**{!! $md($label) !!}:** {!! $md($value) !!}<br>
@endforeach
</x-mail::panel>

{{ setting('general.site_name') }}
</x-mail::message>
