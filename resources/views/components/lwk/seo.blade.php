@props(['seo'])
@php
    /** @var \App\Modules\Seo\SeoData $seo */
    $og = $seo->ogImageUrl();
    $locale = str_replace('_', '-', app()->getLocale());
@endphp
<title>{{ $seo->fullTitle() }}</title>
<meta name="description" content="{{ $seo->description }}">
<link rel="canonical" href="{{ $seo->canonical }}">
<meta name="robots" content="{{ $seo->noindex ? 'noindex, follow' : 'index, follow, max-image-preview:large, max-snippet:-1' }}">
@foreach ($seo->alternates as $lang => $href)
    <link rel="alternate" hreflang="{{ $lang }}" href="{{ $href }}">
@endforeach
@if ($seo->prev)<link rel="prev" href="{{ $seo->prev }}">@endif
@if ($seo->next)<link rel="next" href="{{ $seo->next }}">@endif
<meta property="og:site_name" content="{{ \App\Modules\Seo\SeoData::siteName() }}">
<meta property="og:type" content="{{ $seo->type }}">
<meta property="og:title" content="{{ $seo->title }}">
<meta property="og:description" content="{{ $seo->description }}">
<meta property="og:url" content="{{ $seo->canonical }}">
<meta property="og:locale" content="{{ str_replace('-', '_', $locale) }}">
@if ($og)
    <meta property="og:image" content="{{ $og }}">
    <meta property="og:image:width" content="{{ \App\Modules\Seo\OgImage::WIDTH }}">
    <meta property="og:image:height" content="{{ \App\Modules\Seo\OgImage::HEIGHT }}">
    @if ($seo->image?->alt)<meta property="og:image:alt" content="{{ $seo->image->alt }}">@endif
@endif
@if ($seo->content && $seo->type === 'article')
    <meta property="article:published_time" content="{{ $seo->content->published_at?->toIso8601String() }}">
    <meta property="article:modified_time" content="{{ $seo->content->updated_at?->toIso8601String() }}">
@endif
<meta name="twitter:card" content="{{ $og ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $seo->title }}">
<meta name="twitter:description" content="{{ $seo->description }}">
@if ($og)<meta name="twitter:image" content="{{ $og }}">@endif
@if ($v = setting('analytics.google_site_verification'))<meta name="google-site-verification" content="{{ $v }}">@endif
@if ($v = setting('analytics.bing_site_verification'))<meta name="msvalidate.01" content="{{ $v }}">@endif
<link rel="alternate" type="application/rss+xml" title="{{ \App\Modules\Seo\SeoData::siteName() }}" href="{{ url('/feed.xml') }}">
@if ($seo->content && ! $seo->noindex)
    <link rel="alternate" type="text/markdown" href="{{ $seo->content->url() }}.md">
@endif
<script type="application/ld+json">{!! json_encode($seo->jsonLd(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
