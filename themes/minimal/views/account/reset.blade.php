@extends('theme::account.layout')

@section('auth')
    <form method="POST" action="{{ route('members.password.update') }}" class="lwk-form">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        @include('theme::account._field', ['name' => 'email', 'type' => 'email', 'label' => __('larawebkit-members.fields.email'), 'value' => $email, 'autocomplete' => 'email'])
        @include('theme::account._field', ['name' => 'password', 'type' => 'password', 'label' => __('larawebkit-members.fields.password_new'), 'autocomplete' => 'new-password'])
        @include('theme::account._field', ['name' => 'password_confirmation', 'type' => 'password', 'label' => __('larawebkit-members.fields.password_confirm'), 'autocomplete' => 'new-password'])
        <button type="submit" class="lwk-button">{{ __('larawebkit-members.reset.submit') }}</button>
    </form>
@endsection
