@extends('errors.layout')

@section('code', '429')
@section('title', __('larawebkit-front.errors.too_many.title'))
@section('message', __('larawebkit-front.errors.too_many.text'))
@section('extra')
    <p class="lwk-buttons lwk-system-actions"><a href="{{ url('/') }}" class="lwk-button">{{ __('larawebkit-front.back_home') }}</a></p>
@endsection
