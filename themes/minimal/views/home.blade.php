@extends(app(\App\Modules\Themes\ThemeManager::class)->view('layout'))

@section('content')
    <section class="lwk-home-intro">
        <div class="lwk-container">
            <h1>{{ setting('general.site_name') }}</h1>
            @if (setting('general.tagline'))<p class="lwk-lead">{{ setting('general.tagline') }}</p>@endif
        </div>
    </section>
    <section class="lwk-container lwk-listing" aria-labelledby="latest">
        <div class="lwk-section-head is-split">
            <h2 id="latest" class="lwk-section-heading">{{ __('larawebkit-front.latest') }}</h2>
            @if ($posts->isNotEmpty())<a href="{{ localized_path('/blog') }}" class="lwk-more">{{ __('larawebkit-front.all_posts') }} <span aria-hidden="true">→</span></a>@endif
        </div>
        @if ($posts->isEmpty())
            <div class="lwk-empty"><p>{{ __('larawebkit-front.no_posts') }}</p></div>
        @else
            <div class="lwk-cards lwk-cards-featured">
                @foreach ($posts as $post)
                    <x-lwk.post-card :post="$post" :priority="$loop->first" :featured="$loop->first" />
                @endforeach
            </div>
        @endif
        <x-lwk.newsletter class="lwk-home-newsletter" />
    </section>
@endsection
