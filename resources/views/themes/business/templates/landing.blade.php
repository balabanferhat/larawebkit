@extends(app(\App\Modules\Themes\ThemeManager::class)->view('layout'))

@php($rendered = \App\Modules\Themes\ContentRenderer::prepare($page->content))

@push('head')
    @if ($page->featuredMedia){{ \App\Modules\Media\Support\ResponsiveImage::preload($page->featuredMedia, '100vw') }}@endif
@endpush

@section('content')
    <section class="lwk-hero-band">
        <div class="lwk-container">
            <h1>{{ $page->title }}</h1>
            @if ($page->summary)<p class="lwk-lead">{{ $page->summary }}</p>@endif
        </div>
    </section>
    @if ($page->featuredMedia)
        <div class="lwk-container"><figure class="lwk-hero"><x-lwk.image :media="$page->featuredMedia" :priority="true" sizes="100vw" /></figure></div>
    @endif
    <article class="lwk-container lwk-article lwk-wide">
        <div class="lwk-prose">{!! $rendered['html'] !!}</div>
        @if (! empty($page->faq))
            <section class="lwk-faq" aria-labelledby="faq">
                <h2 id="faq">{{ __('larawebkit-front.faq') }}</h2>
                @foreach ($page->faq as $item)
                    <details><summary>{{ $item['question'] ?? '' }}</summary><p>{!! nl2br(e($item['answer'] ?? '')) !!}</p></details>
                @endforeach
            </section>
        @endif
    </article>
@endsection
