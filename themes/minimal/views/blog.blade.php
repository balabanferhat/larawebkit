@extends(app(\App\Modules\Themes\ThemeManager::class)->view('layout'))

@section('content')
    <div class="lwk-container lwk-listing">
        <header class="lwk-listing-head">
            <x-lwk.breadcrumbs :seo="$seo" />
            <h1>{{ $heading }}</h1>
            @if (! empty($description))<p class="lwk-lead">{{ $description }}</p>@endif
        </header>
        @if ($posts->isEmpty())
            <div class="lwk-empty">
                <p>{{ __('larawebkit-front.no_posts') }}</p>
                <p><a href="{{ localized_path('/') }}">{{ __('larawebkit-front.back_home') }}</a></p>
            </div>
        @else
            <div class="lwk-cards">
                @foreach ($posts as $post)
                    <x-lwk.post-card :post="$post" :priority="$loop->first && $posts->currentPage() === 1" />
                @endforeach
            </div>
        @endif
        @include('theme::partials.pagination')
    </div>
@endsection
