@props(['post', 'priority' => false, 'featured' => false, 'heading' => 'h2', 'showExcerpt' => true])
@php($category = $post->relationLoaded('categories') ? $post->categories->first() : null)
<article @class(['lwk-card', 'is-featured' => $featured])>
    @if ($post->featuredMedia)
        <a href="{{ $post->publicPath() }}" tabindex="-1" aria-hidden="true" class="lwk-card-media">
            <x-lwk.image :media="$post->featuredMedia" :size="$featured ? 1280 : 640" :sizes="$featured ? '(min-width: 960px) 60vw, 100vw' : '(min-width: 960px) 33vw, 100vw'" :priority="$priority" alt="" />
        </a>
    @endif
    <div class="lwk-card-body">
        @if ($category)<a href="{{ $category->publicPath() }}" class="lwk-card-kicker">{{ $category->name }}</a>@endif
        <{{ $heading }} class="lwk-card-title"><a href="{{ $post->publicPath() }}">{{ $post->title }}</a></{{ $heading }}>
        @if ($showExcerpt)<p class="lwk-card-excerpt">{{ \Illuminate\Support\Str::limit($post->excerpt ?: $post->seoDescription(), $featured ? 240 : 150) }}</p>@endif
        <p class="lwk-meta">
            @if ($post->relationLoaded('author') && $post->author)<span>{{ $post->author->name }}</span> · @endif
            <time datetime="{{ $post->published_at?->toDateString() }}">{{ $post->published_at?->isoFormat('LL') }}</time>
            · {{ __('larawebkit-front.reading_time', ['minutes' => \App\Modules\Themes\ContentRenderer::readingMinutes($post->word_count)]) }}
        </p>
    </div>
</article>
