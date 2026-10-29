<x-mail::message>
# {{ __('larawebkit-mail.newsletter.heading', [], $locale) }}

{{ __('larawebkit-mail.newsletter.body', ['site' => setting('general.site_name')], $locale) }}

<x-mail::button :url="$url">
{{ __('larawebkit-mail.newsletter.button', [], $locale) }}
</x-mail::button>

{{ __('larawebkit-mail.newsletter.ignore', [], $locale) }}

{{ setting('general.site_name') }}
</x-mail::message>
