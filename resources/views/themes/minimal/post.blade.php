@extends(app(\App\Modules\Themes\ThemeManager::class)->view('layout'))

@php($rendered = \App\Modules\Themes\ContentRenderer::prepare($post->content))

@push('head')
    @if ($post->featuredMedia){{ \App\Modules\Media\Support\ResponsiveImage::preload($post->featuredMedia, '(min-width: 768px) 768px, 100vw') }}@endif
@endpush

@section('content')
    <article class="lwk-container lwk-article">
        <x-lwk.breadcrumbs :seo="$seo" />
        <header>
            <h1>{{ $post->title }}</h1>
            <p class="lwk-meta">
                @if ($post->author)<a href="{{ localized_path('/author/'.$post->author->slug) }}" rel="author">{{ $post->author->name }}</a> · @endif
                {{ __('larawebkit-front.published') }} <time datetime="{{ $post->published_at?->toIso8601String() }}">{{ $post->published_at?->isoFormat('LL') }}</time>
                @if ($post->updated_at && $post->published_at && $post->updated_at->gt($post->published_at->copy()->addDay()))
                    · {{ __('larawebkit-front.updated') }} <time datetime="{{ $post->updated_at->toIso8601String() }}">{{ $post->updated_at->isoFormat('LL') }}</time>
                @endif
                · {{ __('larawebkit-front.reading_time', ['minutes' => \App\Modules\Themes\ContentRenderer::readingMinutes($post->word_count)]) }}
            </p>
            @if ($post->featuredMedia)
                <figure class="lwk-hero">
                    <x-lwk.image :media="$post->featuredMedia" :priority="true" sizes="(min-width: 768px) 768px, 100vw" />
                    @if ($credit = \App\Modules\Media\Support\ResponsiveImage::attribution($post->featuredMedia))
                        <figcaption class="lwk-credit">{{ $credit }}</figcaption>
                    @elseif ($post->featuredMedia->caption)
                        <figcaption>{{ $post->featuredMedia->caption }}</figcaption>
                    @endif
                </figure>
            @endif
        </header>

        @unless ($locked)<x-lwk.toc :toc="$rendered['toc']" />@endunless
        <x-lwk.article-body :content="$post" :body="$rendered['html']" :locked="$locked" />

        @if ($post->categories->isNotEmpty() || $post->tags->isNotEmpty())
            <p class="lwk-taxonomies">
                @foreach ($post->categories as $category)<a href="{{ $category->publicPath() }}" class="lwk-chip">{{ $category->name }}</a>@endforeach
                @foreach ($post->tags as $tag)<a href="{{ $tag->publicPath() }}" class="lwk-chip">#{{ $tag->name }}</a>@endforeach
            </p>
        @endif

        <x-lwk.author-box :author="$post->author" />

        @if ($related->isNotEmpty())
            <section class="lwk-related" aria-labelledby="related">
                <h2 id="related">{{ __('larawebkit-front.related') }}</h2>
                <ul>
                    @foreach ($related as $item)
                        <li><a href="{{ $item->publicPath() }}">{{ $item->title }}</a></li>
                    @endforeach
                </ul>
            </section>
        @endif
    </article>
@endsection
