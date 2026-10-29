@extends('errors.layout')

@section('code', '500')
@section('title', __('larawebkit-front.errors.server.title'))
@section('message', __('larawebkit-front.errors.server.text'))
@section('extra')
    <p class="lwk-buttons lwk-system-actions"><a href="{{ url('/') }}" class="lwk-button">{{ __('larawebkit-front.back_home') }}</a></p>
@endsection
