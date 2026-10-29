@extends(app(\App\Modules\Themes\ThemeManager::class)->view('layout'))

@php($rendered = \App\Modules\Themes\ContentRenderer::prepare($post->content))

@push('head')
    @if ($post->featuredMedia){{ \App\Modules\Media\Support\ResponsiveImage::preload($post->featuredMedia, '(min-width: 72rem) 72rem, 100vw') }}@endif
@endpush

@section('body_class', 'lwk-single')

@section('content')
    <article class="lwk-post">
        <header class="lwk-container lwk-post-head">
            <x-lwk.breadcrumbs :seo="$seo" />
            @if ($category = $post->categories->first())<a href="{{ $category->publicPath() }}" class="lwk-card-kicker">{{ $category->name }}</a>@endif
            <h1>{{ $post->title }}</h1>
            @if (filled($post->excerpt))<p class="lwk-lead">{{ $post->excerpt }}</p>@endif
            @include('theme::partials.post-meta')
        </header>
        @if ($post->featuredMedia)
            <figure class="lwk-container lwk-post-image">
                <x-lwk.image :media="$post->featuredMedia" :priority="true" sizes="(min-width: 72rem) 72rem, 100vw" />
                @if ($credit = \App\Modules\Media\Support\ResponsiveImage::attribution($post->featuredMedia))
                    <figcaption class="lwk-credit">{{ $credit }}</figcaption>
                @elseif ($post->featuredMedia->caption)
                    <figcaption>{{ $post->featuredMedia->caption }}</figcaption>
                @endif
            </figure>
        @endif

        <div class="lwk-container lwk-post-body">
            @unless ($locked)<x-lwk.toc :toc="$rendered['toc']" />@endunless
            <x-lwk.article-body :content="$post" :body="$rendered['html']" :locked="$locked" />

            @if ($post->categories->isNotEmpty() || $post->tags->isNotEmpty())
                <p class="lwk-taxonomies">
                    @foreach ($post->categories as $category)<a href="{{ $category->publicPath() }}" class="lwk-chip">{{ $category->name }}</a>@endforeach
                    @foreach ($post->tags as $tag)<a href="{{ $tag->publicPath() }}" class="lwk-chip">#{{ $tag->name }}</a>@endforeach
                </p>
            @endif

            <x-lwk.author-box :author="$post->author" />
        </div>

        @if ($related->isNotEmpty())
            <section class="lwk-container lwk-related" aria-labelledby="related">
                <h2 id="related">{{ __('larawebkit-front.related') }}</h2>
                <div class="lwk-cards">
                    @foreach ($related as $item)
                        <x-lwk.post-card :post="$item" heading="h3" :show-excerpt="false" />
                    @endforeach
                </div>
            </section>
        @endif
    </article>
@endsection
