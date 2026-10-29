@extends('theme::account.layout')

@section('auth')
    <p>{{ __('larawebkit-members.verify.text', ['email' => \App\Modules\Members\Members::current()?->email]) }}</p>
    <form method="POST" action="{{ route('members.verification.send') }}">
        @csrf
        <button type="submit" class="lwk-button lwk-button-secondary">{{ __('larawebkit-members.verify.resend') }}</button>
    </form>
@endsection
