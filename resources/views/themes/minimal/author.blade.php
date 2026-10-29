@extends(app(\App\Modules\Themes\ThemeManager::class)->view('layout'))

@section('content')
    <div class="lwk-container">
        <x-lwk.breadcrumbs :seo="$seo" />
        <header class="lwk-author lwk-author-page">
            @if ($author->avatar)<x-lwk.image :media="$author->avatar" size="thumb" class="lwk-avatar" :alt="$author->name" :priority="true" />@endif
            <div>
                <h1>{{ $author->name }}</h1>
                @if ($author->job_title)<p class="lwk-meta">{{ $author->job_title }}</p>@endif
                @if ($author->bio)<p>{{ $author->bio }}</p>@endif
                @if ($author->expertise)<p class="lwk-meta">{{ $author->expertise }}</p>@endif
                @if ($author->same_as)
                    <p>@foreach ($author->same_as as $link)<a href="{{ $link }}" rel="me noopener" target="_blank">{{ parse_url($link, PHP_URL_HOST) ?: $link }}</a>@if (! $loop->last) · @endif @endforeach</p>
                @endif
            </div>
        </header>
        <h2 class="lwk-section-title">{{ __('larawebkit-front.posts_by', ['name' => $author->name]) }}</h2>
        @forelse ($posts as $post)
            <x-lwk.post-card :post="$post" />
        @empty
            <p>{{ __('larawebkit-front.no_posts') }}</p>
        @endforelse
    </div>
@endsection
