@extends(app(\App\Modules\Themes\ThemeManager::class)->view('layout'))

@section('content')
    <div class="lwk-container lwk-listing">
        <header class="lwk-listing-head lwk-author-head">
            <x-lwk.breadcrumbs :seo="$seo" />
            @if ($author->avatar)<x-lwk.image :media="$author->avatar" size="thumb" class="lwk-avatar lwk-avatar-lg" :alt="$author->name" :priority="true" />@endif
            <div>
                <h1>{{ $author->name }}</h1>
                @if ($author->job_title)<p class="lwk-eyebrow">{{ $author->job_title }}</p>@endif
                @if ($author->bio)<p class="lwk-lead">{{ $author->bio }}</p>@endif
                @if ($author->expertise)<p class="lwk-meta">{{ $author->expertise }}</p>@endif
                @if ($author->same_as)
                    <p class="lwk-meta">@foreach ($author->same_as as $link)<a href="{{ $link }}" rel="me noopener" target="_blank">{{ parse_url($link, PHP_URL_HOST) ?: $link }}</a>@if (! $loop->last) · @endif @endforeach</p>
                @endif
            </div>
        </header>
        <h2 class="lwk-section-heading lwk-author-posts">{{ __('larawebkit-front.posts_by', ['name' => $author->name]) }}</h2>
        @if ($posts->isEmpty())
            <div class="lwk-empty"><p>{{ __('larawebkit-front.no_posts') }}</p></div>
        @else
            <div class="lwk-cards">
                @foreach ($posts as $post)<x-lwk.post-card :post="$post" heading="h3" />@endforeach
            </div>
        @endif
        @include('theme::partials.pagination')
    </div>
@endsection
