@props(['author'])
@if ($author)
    <aside class="lwk-author" aria-label="{{ __('larawebkit-front.about_author') }}">
        @if ($author->avatar)
            <x-lwk.image :media="$author->avatar" size="thumb" class="lwk-avatar" :alt="$author->name" />
        @endif
        <div>
            <p class="lwk-author-name"><a href="{{ localized_path('/author/'.$author->slug) }}" rel="author">{{ $author->name }}</a>@if ($author->job_title) · <span>{{ $author->job_title }}</span>@endif</p>
            @if ($author->bio)<p>{{ $author->bio }}</p>@endif
        </div>
    </aside>
@endif
