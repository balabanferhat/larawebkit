@extends(app(\App\Modules\Themes\ThemeManager::class)->view('layout'))

@php($rendered = \App\Modules\Themes\ContentRenderer::prepare($page->content))

@push('head')
    @if ($page->featuredMedia){{ \App\Modules\Media\Support\ResponsiveImage::preload($page->featuredMedia) }}@endif
@endpush

@section('content')
    <article class="lwk-container lwk-article {{ $page->template === 'full-width' ? 'lwk-wide' : '' }}">
        <x-lwk.breadcrumbs :seo="$seo" />
        <h1>{{ $page->title }}</h1>
        @if ($page->featuredMedia)
            <figure class="lwk-hero"><x-lwk.image :media="$page->featuredMedia" :priority="true" /></figure>
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
