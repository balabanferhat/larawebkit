<p class="lwk-post-meta">
    @if ($post->author)
        @if ($post->author->avatar)<x-lwk.image :media="$post->author->avatar" size="thumb" class="lwk-avatar lwk-avatar-sm" alt="" />@endif
        <a href="{{ localized_path('/author/'.$post->author->slug) }}" rel="author">{{ $post->author->name }}</a>
        <span aria-hidden="true">·</span>
    @endif
    <time datetime="{{ $post->published_at?->toIso8601String() }}">{{ $post->published_at?->isoFormat('LL') }}</time>
    @if ($post->updated_at && $post->published_at && $post->updated_at->gt($post->published_at->copy()->addDay()))
        <span aria-hidden="true">·</span> <span>{{ __('larawebkit-front.updated') }} <time datetime="{{ $post->updated_at->toIso8601String() }}">{{ $post->updated_at->isoFormat('LL') }}</time></span>
    @endif
    <span aria-hidden="true">·</span>
    <span>{{ __('larawebkit-front.reading_time', ['minutes' => \App\Modules\Themes\ContentRenderer::readingMinutes($post->word_count)]) }}</span>
</p>
