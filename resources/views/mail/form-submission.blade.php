@php($md = fn ($v) => str_replace(['\\', '`', '*', '_', '[', ']', '(', ')', '#', '!', '|'], ['\\\\', '\`', '\*', '\_', '\[', '\]', '\(', '\)', '\#', '\!', '\|'], e((string) $v)))
<x-mail::message>
# {{ __('larawebkit-forms.mail.received_heading', ['form' => $submission->form->name]) }}

@foreach ($rows as $label => $value)
**{!! $md($label) !!}:**
{!! nl2br($md($value)) !!}

@endforeach
@if ($submission->consent_version)
{{ __('larawebkit-forms.mail.consent', ['version' => $submission->consent_version]) }}
@endif

<x-mail::button :url="\App\Modules\Forms\Filament\Resources\FormSubmissions\FormSubmissionResource::getUrl('view', ['record' => $submission], panel: 'admin')">
{{ __('larawebkit-forms.mail.open_admin') }}
</x-mail::button>

{{ setting('general.site_name') }}
</x-mail::message>
