@extends('errors.layout')

@section('code', '419')
@section('title', __('larawebkit-front.errors.expired.title'))
@section('message', __('larawebkit-front.errors.expired.text'))
@section('extra')
    <p class="lwk-buttons lwk-system-actions"><a href="{{ url('/') }}" class="lwk-button">{{ __('larawebkit-front.back_home') }}</a></p>
@endsection
