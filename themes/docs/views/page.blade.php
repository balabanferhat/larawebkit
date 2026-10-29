@extends(app(\App\Modules\Themes\ThemeManager::class)->view('layout'))

{{-- Docs: section navigation on the left, the page in the middle, "On this page" on the right, previous/next at the bottom. --}}
@php
    $rendered = \App\Modules\Themes\ContentRenderer::prepare($page->content, 1);
    $flat = \App\Modules\Content\Models\Page::published()->where('locale', app()->getLocale())->whereNotIn('template', ['legal'])->where('path', '!=', 'home')
        ->orderBy('sort_order')->orderBy('title')->get(['id', 'parent_id', 'title', 'path', 'locale', 'sort_order']);
    $ordered = collect();
    foreach ($flat->whereNull('parent_id') as $root) { $ordered->push($root); foreach ($flat->where('parent_id', $root->id) as $child) { $ordered->push($child); } }
    $i = $ordered->search(fn ($p) => $p->id === $page->id);
    $prev = $i !== false && $i > 0 ? $ordered[$i - 1] : null;
    $next = $i !== false ? $ordered[$i + 1] ?? null : null;
@endphp

@section('body_class', 'lwk-docs-body')

@section('content')
    <div class="lwk-container lwk-docs">
        @include('theme::partials.docs-nav')
        <article class="lwk-docs-article">
            <x-lwk.breadcrumbs :seo="$seo" />
            <h1>{{ $page->title }}</h1>
            @if (filled($page->summary))<p class="lwk-lead">{{ $page->summary }}</p>@endif
            @if ($page->hasBlocks())<x-lwk.blocks :blocks="$page->blockList()" />@endif
            <div class="lwk-prose">{!! $rendered['html'] !!}</div>
            @if (isset($children) && $children->isNotEmpty())
                <ul class="lwk-docs-children" role="list">
                    @foreach ($children as $child)<li><a href="{{ $child->publicPath() }}"><strong>{{ $child->title }}</strong>@if ($child->summary)<span>{{ $child->summary }}</span>@endif</a></li>@endforeach
                </ul>
            @endif
            <p class="lwk-meta lwk-docs-updated">{{ __('larawebkit-front.updated') }} <time datetime="{{ $page->updated_at?->toIso8601String() }}">{{ $page->updated_at?->isoFormat('LL') }}</time></p>
            @if ($prev || $next)
                <nav class="lwk-docs-pager" aria-label="{{ __('larawebkit-front.pagination') }}">
                    @if ($prev)<a href="{{ $prev->publicPath() }}" rel="prev"><span class="lwk-meta">{{ __('larawebkit-front.previous') }}</span>{{ $prev->title }}</a>@else<span></span>@endif
                    @if ($next)<a href="{{ $next->publicPath() }}" rel="next" class="is-next"><span class="lwk-meta">{{ __('larawebkit-front.next') }}</span>{{ $next->title }}</a>@endif
                </nav>
            @endif
            @yield('after-content')
        </article>
        <aside class="lwk-docs-toc"><x-lwk.toc :toc="$rendered['toc']" :title="__('larawebkit-front.docs.on_this_page')" /></aside>
    </div>
@endsection
