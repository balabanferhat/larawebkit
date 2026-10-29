@if ($enabled)
    <div class="lwk-container">
        <div class="lwk-section-head is-split">
            <div>
                @if (filled($data['eyebrow'] ?? null))<p class="lwk-eyebrow">{{ $data['eyebrow'] }}</p>@endif
                @if (filled($data['heading'] ?? null))<h2 class="lwk-section-heading">{{ $data['heading'] }}</h2>@endif
            </div>
            <a href="{{ \App\Modules\Events\Events::indexPath() }}" class="lwk-more">{{ ($data['link_label'] ?? null) ?: __('larawebkit-events.upcoming') }} <span aria-hidden="true">→</span></a>
        </div>
        @if ($events->isEmpty())
            <p class="lwk-empty">{{ __('larawebkit-events.none') }}</p>
        @elseif ($variant === 'list')
            <ul class="lwk-post-list" role="list">
                @foreach ($events as $event)
                    <li><a href="{{ $event->publicPath() }}">{{ $event->title }}</a> <time class="lwk-meta" datetime="{{ $event->localStart()->toIso8601String() }}">{{ $event->localStart()->isoFormat('LLL') }}</time></li>
                @endforeach
            </ul>
        @else
            <div class="lwk-cards">
                @foreach ($events as $event)
                    @include('theme::events._card', ['event' => $event, 'level' => 3])
                @endforeach
            </div>
        @endif
    </div>
@endif
