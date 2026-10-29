@extends(app(\App\Modules\Themes\ThemeManager::class)->view('layout'))

{{--
    Magazine front page without a "home" page of its own: the "magazine" block preset (lead story, latest rail,
    more stories, newsletter) with one row per busiest category before the newsletter. A site that wants a
    different front page creates a page with the path "home" (the same preset is offered in the page editor).
--}}
@php
    $locale = app()->getLocale();
    $preset = \App\Modules\Blocks\BlockPresets::blocks('magazine');
    $sections = \App\Modules\Content\Models\Category::query()
        ->whereHas('posts', fn ($q) => $q->published()->where('locale', $locale))
        ->withCount(['posts' => fn ($q) => $q->published()->where('locale', $locale)])->orderByDesc('posts_count')->limit(3)->get()
        ->map(fn ($c) => ['type' => 'latest_posts', 'data' => ['variant' => 'cards', 'heading' => $c->name, 'category_id' => $c->id, 'count' => 4, 'link_label' => __('theme-magazine::theme.more_in', ['category' => $c->name])]])
        ->all();
    $blocks = [$preset[0], ...$sections, ...array_slice($preset, 2)];
@endphp

@section('content')
    <h1 class="lwk-sr-only">{{ setting('general.site_name') }}</h1>
    @if ($posts->isEmpty())
        <div class="lwk-container lwk-listing"><div class="lwk-empty"><p>{{ __('larawebkit-front.no_posts') }}</p></div></div>
    @else
        <x-lwk.blocks :blocks="$blocks" />
    @endif
@endsection
