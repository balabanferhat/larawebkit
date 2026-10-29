@extends(app(\App\Modules\Themes\ThemeManager::class)->view('layout'))

@section('body_class', 'lwk-single')

@section('content')
    <article class="lwk-post lwk-event">
        <header class="lwk-container lwk-post-head">
            <x-lwk.breadcrumbs :seo="$seo" />
            <a href="{{ \App\Modules\Events\Events::indexPath() }}" class="lwk-card-kicker">{{ __('larawebkit-events.plural') }}</a>
            <h1>{{ $event->title }}</h1>
            @if (filled($event->summary))<p class="lwk-lead">{{ $event->summary }}</p>@endif
        </header>
        @if ($event->featuredMedia)
            <figure class="lwk-container lwk-post-image">
                <x-lwk.image :media="$event->featuredMedia" :priority="true" sizes="(min-width: 72rem) 72rem, 100vw" />
            </figure>
        @endif
        <div class="lwk-container lwk-post-body lwk-event-body">
            <div class="lwk-prose">
                @include('theme::events._details')
                @if ($event->status === 'cancelled')
                    <p class="lwk-notice" role="status">{{ __('larawebkit-events.event_cancelled') }}</p>
                @endif
                {!! $event->description !!}
            </div>

            <aside class="lwk-event-register" id="register" aria-labelledby="register-title">
                <h2 id="register-title">{{ __('larawebkit-events.register.title') }}</h2>
                @if (session('event_status'))
                    <p class="lwk-notice lwk-notice-success" role="status">{{ session('event_status') }}</p>
                @elseif ($event->status === 'cancelled')
                    <p>{{ __('larawebkit-events.event_cancelled') }}</p>
                @elseif ($event->isPast())
                    <p>{{ __('larawebkit-events.ended') }}</p>
                @elseif ($event->isFull())
                    <p>{{ __('larawebkit-events.full') }}</p>
                @elseif (! $event->canRegister())
                    <p>{{ __('larawebkit-events.closed') }}</p>
                @else
                    <form method="POST" action="{{ route('events.register', $event->slug) }}" class="lwk-form">
                        @csrf
                        <x-lwk.honeypot />
                        <input type="hidden" name="locale" value="{{ $event->locale }}">
                        @if ($member)
                            <p>{{ __('larawebkit-events.register.as_member', ['name' => $member->name, 'email' => $member->email]) }}</p>
                        @else
                            @include('theme::account._field', ['name' => 'name', 'label' => __('larawebkit-events.fields.name'), 'autocomplete' => 'name'])
                            @include('theme::account._field', ['name' => 'email', 'type' => 'email', 'label' => __('larawebkit-events.fields.email'), 'autocomplete' => 'email'])
                        @endif
                        <label class="lwk-choice">
                            <input type="checkbox" name="consent" value="1" required @error('consent') aria-invalid="true" @enderror>
                            <span>{{ __('larawebkit-events.fields.consent') }} <a href="{{ localized_path('/privacy-policy') }}">{{ __('larawebkit-members.register.privacy') }}</a>.</span>
                        </label>
                        @error('consent')<p class="lwk-error">{{ $message }}</p>@enderror
                        <button type="submit" class="lwk-button">{{ __('larawebkit-events.register.submit') }}</button>
                        @if ($event->isOnline())<p class="lwk-meta">{{ __('larawebkit-events.register.online_note') }}</p>@endif
                    </form>
                @endif
            </aside>
        </div>
    </article>
@endsection
