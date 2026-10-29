@extends(app(\App\Modules\Themes\ThemeManager::class)->view('layout'))

{{-- Docs home: a search-first hero and the top-level sections as cards. --}}
@php
    $sections = \App\Modules\Content\Models\Page::published()->where('locale', app()->getLocale())->whereNull('parent_id')
        ->whereNotIn('template', ['legal'])->orderBy('sort_order')->orderBy('title')->withCount(['children' => fn ($q) => $q->published()])->get();
@endphp

@section('content')
    <section class="lwk-docs-hero">
        <div class="lwk-container">
            <p class="lwk-eyebrow">{{ __('larawebkit-front.docs.navigation') }}</p>
            <h1>{{ setting('general.site_name') }}</h1>
            @if (setting('general.tagline'))<p class="lwk-lead">{{ setting('general.tagline') }}</p>@endif
            <form action="{{ localized_path('/search') }}" method="GET" role="search" class="lwk-docs-search">
                <label for="docs-q" class="lwk-sr-only">{{ __('larawebkit-front.search') }}</label>
                {{ \App\Modules\Themes\Support\Icons::ui('magnifying-glass') }}
                <input id="docs-q" type="search" name="q" placeholder="{{ __('larawebkit-front.search_placeholder') }}">
                <button type="submit" class="lwk-button">{{ __('larawebkit-front.search') }}</button>
            </form>
        </div>
    </section>
    <section class="lwk-container lwk-listing">
        @if ($sections->isEmpty())
            <div class="lwk-empty"><p>{{ __('larawebkit-front.no_posts') }}</p></div>
        @else
            <ul class="lwk-docs-cards" role="list">
                @foreach ($sections as $section)
                    <li>
                        <a href="{{ $section->publicPath() }}" class="lwk-docs-card">
                            <h2>{{ $section->title }}</h2>
                            @if ($section->summary)<p>{{ $section->summary }}</p>@endif
                            @if ($section->children_count)<span class="lwk-meta">{{ trans_choice('theme-docs::theme.pages_count', $section->children_count, ['count' => $section->children_count]) }}</span>@endif
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
        @if ($posts->isNotEmpty())
            <div class="lwk-section-head is-split lwk-docs-news">
                <h2 class="lwk-section-heading">{{ __('theme-docs::theme.news') }}</h2>
                <a href="{{ blog_path() }}" class="lwk-more">{{ __('larawebkit-front.all_posts') }} <span aria-hidden="true">→</span></a>
            </div>
            <ul class="lwk-post-list" role="list">
                @foreach ($posts->take(4) as $post)
                    <li><a href="{{ $post->publicPath() }}">{{ $post->title }}</a> <time class="lwk-meta" datetime="{{ $post->published_at?->toDateString() }}">{{ $post->published_at?->isoFormat('LL') }}</time></li>
                @endforeach
            </ul>
        @endif
    </section>
@endsection
