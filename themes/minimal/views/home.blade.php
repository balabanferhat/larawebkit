@extends(app(\App\Modules\Themes\ThemeManager::class)->view('layout'))

@section('content')
    <div class="lwk-container">
        <section class="lwk-intro">
            <h1>{{ setting('general.site_name') }}</h1>
            @if (setting('general.tagline'))<p class="lwk-lead">{{ setting('general.tagline') }}</p>@endif
        </section>
        <section aria-labelledby="latest">
            <h2 id="latest" class="lwk-section-title">{{ __('larawebkit-front.latest') }}</h2>
            @forelse ($posts as $post)
                <x-lwk.post-card :post="$post" :priority="$loop->first" />
            @empty
                <p>{{ __('larawebkit-front.no_posts') }}</p>
            @endforelse
            @if ($posts->isNotEmpty())<p><a href="{{ localized_path('/blog') }}">{{ __('larawebkit-front.blog') }} →</a></p>@endif
        </section>
    </div>
@endsection
