@extends(app(\App\Modules\Themes\ThemeManager::class)->view('layout'))

@section('content')
    <div class="lwk-container">
        <x-lwk.breadcrumbs :seo="$seo" />
        <h1>{{ $heading }}</h1>
        @if (! empty($description))<p class="lwk-lead">{{ $description }}</p>@endif
        <div class="lwk-cards">
            @forelse ($posts as $post)
                <x-lwk.post-card :post="$post" :priority="$loop->first && $posts->currentPage() === 1" />
            @empty
                <p>{{ __('larawebkit-front.no_posts') }}</p>
            @endforelse
        </div>
        @if ($posts->hasPages())
            <nav class="lwk-pagination" aria-label="{{ __('larawebkit-front.pagination') }}">
                @if ($posts->previousPageUrl())<a href="{{ $posts->previousPageUrl() }}" rel="prev">← {{ __('larawebkit-front.previous') }}</a>@endif
                <span>{{ $posts->currentPage() }} / {{ $posts->lastPage() }}</span>
                @if ($posts->nextPageUrl())<a href="{{ $posts->nextPageUrl() }}" rel="next">{{ __('larawebkit-front.next') }} →</a>@endif
            </nav>
        @endif
    </div>
@endsection
