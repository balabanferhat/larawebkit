@if (method_exists($posts, 'hasPages') && $posts->hasPages())
    <nav class="lwk-pagination" aria-label="{{ __('larawebkit-front.pagination') }}">
        @if ($posts->previousPageUrl())<a href="{{ $posts->previousPageUrl() }}" rel="prev">← {{ __('larawebkit-front.previous') }}</a>@endif
        <span>{{ __('larawebkit-front.page_of', ['page' => $posts->currentPage(), 'pages' => $posts->lastPage()]) }}</span>
        @if ($posts->nextPageUrl())<a href="{{ $posts->nextPageUrl() }}" rel="next">{{ __('larawebkit-front.next') }} →</a>@endif
    </nav>
@endif
