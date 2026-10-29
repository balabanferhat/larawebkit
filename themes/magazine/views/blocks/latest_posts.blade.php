{{-- Magazine: the "front" variant is the front-page layout (lead story, two secondary stories, a latest rail). --}}
@if ($variant !== 'front' || $posts->isEmpty())
    @include('components.lwk.blocks.latest_posts')
@else
    @php($lead = $posts->first())
    @php($secondary = $posts->slice(1, 2))
    @php($rail = $posts->slice(3))
    <div class="lwk-container lwk-front">
        @if (filled($data['heading'] ?? null))<h2 class="lwk-sr-only">{{ $data['heading'] }}</h2>@endif
        <div class="lwk-front-lead">
            <x-lwk.post-card :post="$lead" :featured="true" :priority="$first" />
        </div>
        @if ($secondary->isNotEmpty())
            <div class="lwk-front-secondary">
                @foreach ($secondary as $post)<x-lwk.post-card :post="$post" heading="h3" />@endforeach
            </div>
        @endif
        @if ($rail->isNotEmpty())
            <aside class="lwk-front-rail" aria-labelledby="{{ $blockId }}-rail">
                <h2 id="{{ $blockId }}-rail" class="lwk-rule-title">{{ __('larawebkit-front.latest') }}</h2>
                <ol class="lwk-rail" role="list">
                    @foreach ($rail as $post)
                        <li>
                            @if ($c = $post->categories->first())<a href="{{ $c->publicPath() }}" class="lwk-card-kicker">{{ $c->name }}</a>@endif
                            <a href="{{ $post->publicPath() }}" class="lwk-rail-title">{{ $post->title }}</a>
                            <p class="lwk-meta">{{ $post->author?->name }} · <time datetime="{{ $post->published_at?->toDateString() }}">{{ $post->published_at?->isoFormat('ll') }}</time></p>
                        </li>
                    @endforeach
                </ol>
            </aside>
        @endif
    </div>
@endif
