<x-mail::message>
# {{ __('larawebkit-mail.contact.heading') }}

**{{ __('larawebkit-front.contact.name') }}:** {{ $contactMessage->name }}
**{{ __('larawebkit-front.contact.email') }}:** {{ $contactMessage->email }}
@if ($contactMessage->subject)
**{{ __('larawebkit-front.contact.subject') }}:** {{ $contactMessage->subject }}
@endif

<x-mail::panel>
{{ $contactMessage->message }}
</x-mail::panel>

<x-mail::button :url="url(config('larawebkit.admin_path').'/contact-messages')">
{{ __('larawebkit-mail.contact.open_admin') }}
</x-mail::button>

{{ setting('general.site_name') }}
</x-mail::message>
