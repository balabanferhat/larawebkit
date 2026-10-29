@props(['post', 'priority' => false])
<article class="lwk-card">
    @if ($post->featuredMedia)
        <a href="{{ $post->publicPath() }}" tabindex="-1" aria-hidden="true" class="lwk-card-media">
            <x-lwk.image :media="$post->featuredMedia" size="640" sizes="(min-width: 768px) 33vw, 100vw" :priority="$priority" />
        </a>
    @endif
    <div class="lwk-card-body">
        <h2 class="lwk-card-title"><a href="{{ $post->publicPath() }}">{{ $post->title }}</a></h2>
        <p class="lwk-meta">
            <time datetime="{{ $post->published_at?->toDateString() }}">{{ $post->published_at?->isoFormat('LL') }}</time>
            · {{ __('larawebkit-front.reading_time', ['minutes' => \App\Modules\Themes\ContentRenderer::readingMinutes($post->word_count)]) }}
        </p>
        <p>{{ \Illuminate\Support\Str::limit($post->excerpt ?: $post->seoDescription(), 180) }}</p>
    </div>
</article>
