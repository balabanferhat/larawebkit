@php
    $brand = '#3b3fd8';
    try { $brand = app(\App\Modules\Themes\Design\DesignTokens::class)->values()['primary']; } catch (\Throwable) {}
    $site = (string) setting('general.site_name');
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light">
<title>{{ $campaign->subject }}</title>
<style>
    body { margin: 0; padding: 0; background: #f4f5f7; font-family: -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; color: #1b1f24; }
    .wrap { width: 100%; background: #f4f5f7; padding: 24px 0; }
    .card { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; }
    .head { padding: 24px 32px; border-bottom: 4px solid {{ $brand }}; font-size: 18px; font-weight: 700; }
    .body { padding: 28px 32px; font-size: 16px; line-height: 1.6; }
    .body h2 { font-size: 22px; line-height: 1.3; margin: 24px 0 8px; }
    .body h3 { font-size: 18px; margin: 20px 0 8px; }
    .body a { color: {{ $brand }}; }
    .body img { max-width: 100%; height: auto; border-radius: 8px; }
    .body blockquote { margin: 16px 0; padding: 4px 16px; border-left: 3px solid {{ $brand }}; color: #4b5563; }
    .foot { max-width: 600px; margin: 16px auto 0; padding: 0 32px; font-size: 12px; line-height: 1.5; color: #6b7280; text-align: center; }
    .foot a { color: #6b7280; }
    .preheader { display: none !important; visibility: hidden; opacity: 0; height: 0; width: 0; overflow: hidden; mso-hide: all; }
</style>
</head>
<body>
@if ($campaign->preheader)<div class="preheader">{{ $campaign->preheader }}</div>@endif
<div class="wrap">
    <div class="card">
        <div class="head">{{ $site }}</div>
        <div class="body">{!! \App\Modules\Media\Support\ContentImages::render($body, "600px") !!}</div>
    </div>
    <div class="foot">
        <p>{{ __('larawebkit-newsletter.mail.footer', ['site' => $site]) }}</p>
        <p><a href="{{ $unsubscribe }}">{{ __('larawebkit-newsletter.mail.unsubscribe') }}</a></p>
    </div>
</div>
</body>
</html>
