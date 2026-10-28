@props(['content', 'body', 'locked' => false])
{{-- TL;DR, body, FAQ (→ FAQPage schema) and sources: the citability blocks. --}}
@if (filled($content->summary))
    <aside class="lwk-tldr" aria-label="{{ __('larawebkit-front.tldr') }}">
        <strong>{{ __('larawebkit-front.tldr') }}:</strong> {{ $content->summary }}
    </aside>
@endif

@if ($locked)
    <p class="lwk-lead">{{ $content->excerpt }}</p>
    <div class="lwk-notice" role="note"><strong>{{ __('larawebkit-front.members_only') }}</strong> {{ __('larawebkit-front.members_only_hint') }}</div>
@else
    <div class="lwk-prose">{!! $body !!}</div>

    @if (! empty($content->faq))
        <section class="lwk-faq" aria-labelledby="faq">
            <h2 id="faq">{{ __('larawebkit-front.faq') }}</h2>
            @foreach ($content->faq as $item)
                <details>
                    <summary>{{ $item['question'] ?? '' }}</summary>
                    <p>{!! nl2br(e($item['answer'] ?? '')) !!}</p>
                </details>
            @endforeach
        </section>
    @endif

    @if (filled($content->sources))
        <section class="lwk-sources" aria-labelledby="sources">
            <h2 id="sources">{{ __('larawebkit-front.sources') }}</h2>
            <ol>
                @foreach (preg_split('/\R+/', trim($content->sources)) as $source)
                    @php($url = preg_match('~https?://\S+~', $source, $m) ? $m[0] : null)
                    <li>@if ($url)<a href="{{ $url }}" rel="nofollow noopener" target="_blank">{{ trim(str_replace($url, '', $source)) ?: $url }}</a>@else{{ $source }}@endif</li>
                @endforeach
            </ol>
        </section>
    @endif
@endif
