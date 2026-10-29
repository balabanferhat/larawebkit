@extends(app(\App\Modules\Themes\ThemeManager::class)->view('layout'))

@php
    $rendered = \App\Modules\Themes\ContentRenderer::prepare($page->content);
    $blocks = $page->blockList();
    // A hero as the first block carries the page's <h1>.
    $heroFirst = ($blocks[0]['type'] ?? null) === 'hero';
@endphp

@push('head')
    @if ($page->featuredMedia && ! $blocks){{ \App\Modules\Media\Support\ResponsiveImage::preload($page->featuredMedia) }}@endif
@endpush

@if ($blocks)
    @section('main_class', 'lwk-main-blocks')
    @section('content')
        @unless ($heroFirst)
            <header class="lwk-container lwk-page-head">
                <x-lwk.breadcrumbs :seo="$seo" />
                <h1>{{ $page->title }}</h1>
                @if (filled($page->summary))<p class="lwk-lead">{{ $page->summary }}</p>@endif
            </header>
        @endunless
        <x-lwk.blocks :blocks="$blocks" />
        @if (trim(strip_tags((string) $page->content)) !== '' || ! empty($page->faq) || filled($page->sources))
            <article class="lwk-container lwk-article lwk-block-content">
                <x-lwk.article-body :content="$page" :body="$rendered['html']" />
            </article>
        @endif
        @yield('after-content')
    @endsection
@else
    @section('content')
        <article class="lwk-container lwk-article {{ in_array($page->template, ['full-width', 'landing'], true) ? 'lwk-wide' : '' }}">
            <header class="lwk-page-head">
                <x-lwk.breadcrumbs :seo="$seo" />
                <h1>{{ $page->title }}</h1>
            </header>
            @if ($page->featuredMedia)
                <figure class="lwk-hero-image"><x-lwk.image :media="$page->featuredMedia" :priority="true" /></figure>
            @endif
            <x-lwk.toc :toc="$rendered['toc']" />
            <x-lwk.article-body :content="$page" :body="$rendered['html']" />
            @if ($page->template !== 'legal')
                <p class="lwk-meta">{{ __('larawebkit-front.updated') }} <time datetime="{{ $page->updated_at?->toIso8601String() }}">{{ $page->updated_at?->isoFormat('LL') }}</time></p>
            @endif
            @if (isset($children) && $children->isNotEmpty())
                <ul class="lwk-children">
                    @foreach ($children as $child)<li><a href="{{ $child->publicPath() }}">{{ $child->title }}</a></li>@endforeach
                </ul>
            @endif
            @yield('after-content')
        </article>
    @endsection
@endif
