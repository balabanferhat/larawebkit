@extends('theme::account.layout')

@section('auth')
    <form method="POST" action="{{ route('members.login.submit') }}" class="lwk-form">
        @csrf
        @include('theme::account._field', ['name' => 'email', 'type' => 'email', 'label' => __('larawebkit-members.fields.email'), 'autocomplete' => 'email'])
        @include('theme::account._field', ['name' => 'password', 'type' => 'password', 'label' => __('larawebkit-members.fields.password'), 'autocomplete' => 'current-password'])
        <label class="lwk-choice"><input type="checkbox" name="remember" value="1"> <span>{{ __('larawebkit-members.login.remember') }}</span></label>
        <button type="submit" class="lwk-button">{{ __('larawebkit-members.login.submit') }}</button>
    </form>
    <p class="lwk-meta lwk-auth-links"><a href="{{ route('members.password.request') }}">{{ __('larawebkit-members.login.forgot') }}</a></p>
@endsection

@section('auth_after')
    @if (setting('members.registration', true))<p>{{ __('larawebkit-members.login.no_account') }} <a href="{{ route('members.register') }}">{{ __('larawebkit-members.register.title') }}</a></p>@endif
@endsection
