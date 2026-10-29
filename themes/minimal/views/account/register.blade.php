@extends('theme::account.layout')

@section('auth')
    <form method="POST" action="{{ route('members.register.submit') }}" class="lwk-form">
        @csrf
        <x-lwk.honeypot />
        @include('theme::account._field', ['name' => 'name', 'label' => __('larawebkit-members.fields.name'), 'autocomplete' => 'name'])
        @include('theme::account._field', ['name' => 'email', 'type' => 'email', 'label' => __('larawebkit-members.fields.email'), 'autocomplete' => 'email'])
        @include('theme::account._field', ['name' => 'password', 'type' => 'password', 'label' => __('larawebkit-members.fields.password_new'), 'autocomplete' => 'new-password'])
        @include('theme::account._field', ['name' => 'password_confirmation', 'type' => 'password', 'label' => __('larawebkit-members.fields.password_confirm'), 'autocomplete' => 'new-password'])
        <label class="lwk-choice">
            <input type="checkbox" name="terms" value="1" required @error('terms') aria-invalid="true" @enderror>
            <span>{{ __('larawebkit-members.register.terms') }} <a href="{{ localized_path('/privacy-policy') }}">{{ __('larawebkit-members.register.privacy') }}</a></span>
        </label>
        @error('terms')<p class="lwk-error">{{ $message }}</p>@enderror
        <button type="submit" class="lwk-button">{{ __('larawebkit-members.register.submit') }}</button>
    </form>
@endsection

@section('auth_after')
    <p>{{ __('larawebkit-members.register.have_account') }} <a href="{{ route('members.login') }}">{{ __('larawebkit-members.login.title') }}</a></p>
@endsection
