@extends('theme::account.layout')

@section('auth')
    <p class="lwk-meta">{{ __('larawebkit-members.forgot.text') }}</p>
    <form method="POST" action="{{ route('members.password.email') }}" class="lwk-form">
        @csrf
        @include('theme::account._field', ['name' => 'email', 'type' => 'email', 'label' => __('larawebkit-members.fields.email'), 'autocomplete' => 'email'])
        <button type="submit" class="lwk-button">{{ __('larawebkit-members.forgot.submit') }}</button>
    </form>
@endsection

@section('auth_after')
    <p><a href="{{ route('members.login') }}">← {{ __('larawebkit-members.login.title') }}</a></p>
@endsection
