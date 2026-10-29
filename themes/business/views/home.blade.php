@extends(app(\App\Modules\Themes\ThemeManager::class)->view('layout'))

@section('content')
    <section class="lwk-hero-band">
        <div class="lwk-container">
            <h1>{{ setting('general.site_name') }}</h1>
            @if (setting('general.tagline'))<p class="lwk-lead">{{ setting('general.tagline') }}</p>@endif
            <p class="lwk-cta">
                <a href="{{ blog_path() }}" class="lwk-button">{{ __('larawebkit-front.hero.cta_blog') }}</a>
                @if (setting('general.contact_email'))<a href="mailto:{{ setting('general.contact_email') }}" class="lwk-button lwk-button-ghost">{{ __('larawebkit-front.hero.cta_contact') }}</a>@endif
            </p>
        </div>
    </section>
    <div class="lwk-container">
        <h2 class="lwk-section-title">{{ __('larawebkit-front.latest') }}</h2>
        <div class="lwk-cards lwk-cards-3">
            @forelse ($posts as $post)
                <x-lwk.post-card :post="$post" />
            @empty
                <p>{{ __('larawebkit-front.no_posts') }}</p>
            @endforelse
        </div>
    </div>
@endsection
