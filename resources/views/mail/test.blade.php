<x-mail::message>
{{ __('larawebkit-settings.mail_test.body') }}

{{ __('larawebkit-settings.mail_test.details', ['mailer' => $mailer, 'date' => $sentAt]) }}

{{ setting('general.site_name') }}
</x-mail::message>
