@extends('errors.layout')

@section('code', '503')
@section('title', __('larawebkit-front.errors.maintenance.title'))
@section('message', __('larawebkit-front.errors.maintenance.text'))
@section('extra')
    <p class="lwk-meta">{{ __('larawebkit-front.errors.maintenance.hint') }}</p>
@endsection
