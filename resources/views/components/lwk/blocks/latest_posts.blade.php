<div class="lwk-container">
    <div class="lwk-section-head is-split">
        <div>
            @if (filled($data['eyebrow'] ?? null))<p class="lwk-eyebrow">{{ $data['eyebrow'] }}</p>@endif
            @if (filled($data['heading'] ?? null))<h2 class="lwk-section-heading">{{ $data['heading'] }}</h2>@endif
        </div>
        <a href="{{ $category?->publicPath() ?? blog_path() }}" class="lwk-more">{{ ($data['link_label'] ?? null) ?: __('larawebkit-front.blog') }} <span aria-hidden="true">→</span></a>
    </div>
    @if ($posts->isEmpty())
        <p class="lwk-empty">{{ __('larawebkit-front.no_posts') }}</p>
    @elseif ($variant === 'list')
        <ul class="lwk-post-list" role="list">
            @foreach ($posts as $post)
                <li><a href="{{ $post->publicPath() }}">{{ $post->title }}</a> <time class="lwk-meta" datetime="{{ $post->published_at?->toDateString() }}">{{ $post->published_at?->isoFormat('LL') }}</time></li>
            @endforeach
        </ul>
    @else
        {{-- "front" is laid out by themes that support it (magazine); elsewhere it reads as "featured". --}}
        <div @class(['lwk-cards', 'lwk-cards-featured' => in_array($variant, ['featured', 'front'], true)])>
            @foreach ($posts as $post)
                <x-lwk.post-card :post="$post" :featured="in_array($variant, ['featured', 'front'], true) && $loop->first" heading="h3" />
            @endforeach
        </div>
    @endif
</div>
