@extends(app(\App\Modules\Themes\ThemeManager::class)->view('layout'))

{{-- Magazine archives (blog, category, tag): the newest story large on the first page, then the grid; the "archive" block preset below. --}}
@section('content')
    <div class="lwk-container lwk-listing lwk-archive">
        <header class="lwk-listing-head lwk-rule-head">
            <div>
                <x-lwk.breadcrumbs :seo="$seo" />
                <h1>{{ $heading }}</h1>
                @if (! empty($description))<p class="lwk-lead">{{ $description }}</p>@endif
            </div>
        </header>
        @if ($posts->isEmpty())
            <div class="lwk-empty">
                <p>{{ __('larawebkit-front.no_posts') }}</p>
                <p><a href="{{ localized_path('/') }}">{{ __('larawebkit-front.back_home') }}</a></p>
            </div>
        @else
            @php($lead = $posts->currentPage() === 1 ? $posts->first() : null)
            @if ($lead)
                <div class="lwk-archive-lead"><x-lwk.post-card :post="$lead" :featured="true" :priority="true" /></div>
            @endif
            <div class="lwk-cards">
                @foreach ($posts as $post)
                    @continue($lead && $post->is($lead))
                    <x-lwk.post-card :post="$post" :priority="! $lead && $loop->first" />
                @endforeach
            </div>
        @endif
        @include('theme::partials.pagination')
    </div>
    <x-lwk.blocks :blocks="\App\Modules\Blocks\BlockPresets::blocks('archive')" />
@endsection
