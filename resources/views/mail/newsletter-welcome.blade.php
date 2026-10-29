<x-mail::message>
# {{ __('larawebkit-newsletter.mail.welcome_heading') }}

{{ __('larawebkit-newsletter.mail.welcome_body', ['site' => setting('general.site_name')]) }}

<x-mail::button :url="$download">
{{ __('larawebkit-forms.mail.download') }}
</x-mail::button>

{{ __('larawebkit-forms.mail.download_expires', ['hours' => config('larawebkit.download_link_hours', 72)]) }}

{{ setting('general.site_name') }}
</x-mail::message>
