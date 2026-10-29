@extends(app(\App\Modules\Themes\ThemeManager::class)->view('layout'))

@php
    $rendered = \App\Modules\Themes\ContentRenderer::prepare($page->content, 1);
    $nav = \App\Modules\Content\Models\Page::published()->where('locale', app()->getLocale())->whereNull('parent_id')
        ->where('template', '!=', 'legal')->orderBy('sort_order')->orderBy('title')->with(['children' => fn ($q) => $q->published()])->get();
@endphp

@section('content')
    <div class="lwk-container lwk-docs">
        <nav class="lwk-docs-nav" aria-label="{{ __('larawebkit-front.docs.navigation') }}">
            <ul>
                @foreach ($nav as $item)
                    <li>
                        <a href="{{ $item->publicPath() }}" @if ($item->is($page)) aria-current="page" @endif>{{ $item->title }}</a>
                        @if ($item->children->isNotEmpty())
                            <ul>@foreach ($item->children as $child)<li><a href="{{ $child->publicPath() }}" @if ($child->is($page)) aria-current="page" @endif>{{ $child->title }}</a></li>@endforeach</ul>
                        @endif
                    </li>
                @endforeach
            </ul>
        </nav>
        <article class="lwk-article">
            <x-lwk.breadcrumbs :seo="$seo" />
            <h1>{{ $page->title }}</h1>
            <x-lwk.article-body :content="$page" :body="$rendered['html']" />
            <p class="lwk-meta">{{ __('larawebkit-front.updated') }} <time datetime="{{ $page->updated_at?->toIso8601String() }}">{{ $page->updated_at?->isoFormat('LL') }}</time></p>
            @yield('after-content')
        </article>
        <aside class="lwk-docs-toc"><x-lwk.toc :toc="$rendered['toc']" :title="__('larawebkit-front.docs.on_this_page')" /></aside>
    </div>
@endsection
