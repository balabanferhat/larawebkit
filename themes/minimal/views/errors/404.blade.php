@extends(app(\App\Modules\Themes\ThemeManager::class)->view('layout'))

@php
    $gone = ($status ?? 404) === 410;
    $popular = [];
    try {
        if (\App\Modules\Core\ModuleRegistry::enabled('dashboard')) {
            // Most visited pages of the last 30 days (cookieless access log), with their titles.
            $popular = collect(\App\Modules\Dashboard\Analytics::topPages(30, 15))
                ->map(function ($row) {
                    $path = trim((string) $row['path'], '/');
                    $item = str_starts_with($path, 'blog/') ? \App\Modules\Content\Models\Post::published()->where('slug', substr($path, 5))->first()
                        : \App\Modules\Content\Models\Page::published()->where('path', $path)->first();

                    return $item ? ['url' => $item->publicPath(), 'label' => $item->title] : null;
                })->filter()->unique('url')->take(5)->values()->all();
        }
        if ($popular === [] && \App\Modules\Core\ModuleRegistry::enabled('content')) {
            $popular = \App\Modules\Content\Models\Post::published()->where('locale', app()->getLocale())->latest('published_at')->limit(5)->get()
                ->map(fn ($p) => ['url' => $p->publicPath(), 'label' => $p->title])->all();
        }
    } catch (\Throwable) {
        $popular = [];
    }
@endphp

@section('content')
    <div class="lwk-container lwk-system">
        <p class="lwk-system-code" aria-hidden="true">{{ $gone ? '410' : '404' }}</p>
        <h1>{{ $gone ? __('larawebkit-front.gone_text') : __('larawebkit-front.not_found_title') }}</h1>
        <p class="lwk-lead">{{ $gone ? __('larawebkit-front.gone_hint') : __('larawebkit-front.not_found_text') }}</p>
        <form action="{{ localized_path('/search') }}" method="GET" role="search" class="lwk-inline-form">
            <label for="q404" class="lwk-sr-only">{{ __('larawebkit-front.search') }}</label>
            <input id="q404" type="search" name="q" placeholder="{{ __('larawebkit-front.search_placeholder') }}">
            <button type="submit">{{ __('larawebkit-front.search') }}</button>
        </form>
        @if ($popular !== [])
            <nav class="lwk-system-links" aria-labelledby="popular-title">
                <h2 id="popular-title">{{ __('larawebkit-front.popular') }}</h2>
                <ul role="list">@foreach ($popular as $link)<li><a href="{{ $link['url'] }}">{{ $link['label'] }} <span aria-hidden="true">→</span></a></li>@endforeach</ul>
            </nav>
        @endif
        <p class="lwk-buttons lwk-system-actions"><a href="{{ localized_path('/') }}" class="lwk-button">{{ __('larawebkit-front.back_home') }}</a></p>
    </div>
@endsection
