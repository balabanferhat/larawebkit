@extends(app(\App\Modules\Themes\ThemeManager::class)->view('layout'))

{{-- Magazine front page: lead story, two secondary stories, a "latest" rail, then one row per category. --}}
@php
    $locale = app()->getLocale();
    $all = \App\Modules\Content\Models\Post::published()->where('locale', $locale)->with('featuredMedia', 'author', 'categories')->latest('published_at')->limit(24)->get();
    $lead = $all->first();
    $secondary = $all->slice(1, 2);
    $rail = $all->slice(3, 5);
    $shown = collect([$lead?->id]);
    $sections = \App\Modules\Content\Models\Category::query()
        ->whereHas('posts', fn ($q) => $q->published()->where('locale', $locale))
        ->withCount(['posts' => fn ($q) => $q->published()->where('locale', $locale)])->orderByDesc('posts_count')->limit(4)->get();
@endphp

@push('head')
    @if ($lead?->featuredMedia){{ \App\Modules\Media\Support\ResponsiveImage::preload($lead->featuredMedia, '(min-width: 60em) 50vw, 100vw') }}@endif
@endpush

@section('content')
    <h1 class="lwk-sr-only">{{ setting('general.site_name') }}</h1>
    @if (! $lead)
        <div class="lwk-container lwk-listing"><div class="lwk-empty"><p>{{ __('larawebkit-front.no_posts') }}</p></div></div>
    @else
        <section class="lwk-container lwk-front" aria-label="{{ __('theme-magazine::theme.top_stories') }}">
            <div class="lwk-front-lead">
                <x-lwk.post-card :post="$lead" :featured="true" :priority="true" />
            </div>
            <div class="lwk-front-secondary">
                @foreach ($secondary as $post)<x-lwk.post-card :post="$post" heading="h2" />@endforeach
            </div>
            <aside class="lwk-front-rail" aria-labelledby="rail-title">
                <h2 id="rail-title" class="lwk-rule-title">{{ __('larawebkit-front.latest') }}</h2>
                <ol class="lwk-rail" role="list">
                    @foreach ($rail as $post)
                        <li>
                            @if ($c = $post->categories->first())<a href="{{ $c->publicPath() }}" class="lwk-card-kicker">{{ $c->name }}</a>@endif
                            <a href="{{ $post->publicPath() }}" class="lwk-rail-title">{{ $post->title }}</a>
                            <p class="lwk-meta">{{ $post->author?->name }} · <time datetime="{{ $post->published_at?->toDateString() }}">{{ $post->published_at?->isoFormat('ll') }}</time></p>
                        </li>
                    @endforeach
                </ol>
            </aside>
        </section>

        @foreach ($sections as $category)
            @php($posts = $category->posts()->published()->where('locale', $locale)->whereNotIn('posts.id', $shown)->with('featuredMedia', 'author', 'categories')->latest('published_at')->limit(4)->get())
            @if ($posts->isNotEmpty())
                <section class="lwk-container lwk-section-row" aria-labelledby="cat-{{ $category->id }}">
                    <div class="lwk-rule-head">
                        <h2 id="cat-{{ $category->id }}" class="lwk-rule-title">{{ $category->name }}</h2>
                        <a href="{{ $category->publicPath() }}" class="lwk-more">{{ __('theme-magazine::theme.more_in', ['category' => $category->name]) }} <span aria-hidden="true">→</span></a>
                    </div>
                    <div class="lwk-cards lwk-cards-4">
                        @foreach ($posts as $post)<x-lwk.post-card :post="$post" heading="h3" :show-excerpt="false" />@endforeach
                    </div>
                </section>
            @endif
        @endforeach
    @endif
@endsection
