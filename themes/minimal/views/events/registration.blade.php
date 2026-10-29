@extends(app(\App\Modules\Themes\ThemeManager::class)->view('layout'))

@section('no_flash', '1')

@section('content')
    <div class="lwk-container lwk-auth lwk-event-registration">
        <div class="lwk-auth-card">
            <p class="lwk-card-kicker">{{ __('larawebkit-events.registration.title') }}</p>
            <h1>{{ $event->title }}</h1>
            @if (session('status'))<p class="lwk-notice lwk-notice-success" role="status">{{ session('status') }}</p>@endif
            @include('theme::events._details')
            @if ($event->status === 'cancelled')
                <p class="lwk-notice">{{ __('larawebkit-events.event_cancelled') }}</p>
            @elseif ($registration->status === 'cancelled')
                <p>{{ __('larawebkit-events.registration.is_cancelled') }}</p>
                @unless ($event->isPast())<p><a href="{{ $event->publicPath() }}#register">{{ __('larawebkit-events.registration.register_again') }}</a></p>@endunless
            @else
                <p>{{ __('larawebkit-events.registration.confirmed') }}</p>
                @if ($event->isOnline())
                    @if (filled($event->online_url))
                        <p><a href="{{ $event->online_url }}" class="lwk-button" rel="noopener noreferrer" target="_blank">{{ __('larawebkit-events.registration.join') }}</a></p>
                    @else
                        <p class="lwk-meta">{{ __('larawebkit-events.registration.link_later') }}</p>
                    @endif
                @endif
                <p><a href="{{ $registration->icsUrl() }}">{{ __('larawebkit-events.registration.calendar') }}</a></p>
                @unless ($event->isPast())
                    <form method="POST" action="{{ \Illuminate\Support\Facades\URL::signedRoute('events.registration.cancel', ['event' => $event->slug, 'registration' => $registration->id]) }}">
                        @csrf
                        <button type="submit" class="lwk-button-link">{{ __('larawebkit-events.registration.cancel') }}</button>
                    </form>
                @endunless
            @endif
        </div>
    </div>
@endsection
