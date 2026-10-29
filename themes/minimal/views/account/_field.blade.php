{{-- One labelled input with its error, for the account forms. --}}
@php($id = 'acc-'.$name)
<div class="lwk-field">
    <label for="{{ $id }}">{{ $label }}</label>
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type ?? 'text' }}" value="{{ ($type ?? 'text') === 'password' ? '' : old($name, $value ?? '') }}"
        @if ($autocomplete ?? null) autocomplete="{{ $autocomplete }}" @endif @if ($required ?? true) required @endif
        @error($name) aria-invalid="true" aria-describedby="{{ $id }}-error" @enderror>
    @error($name)<p class="lwk-error" id="{{ $id }}-error">{{ $message }}</p>@enderror
</div>
