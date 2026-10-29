@php
    $nav = \App\Modules\Content\Models\Page::published()->where('locale', app()->getLocale())->whereNull('parent_id')
        ->whereNotIn('template', ['legal'])->where('path', '!=', 'home')->orderBy('sort_order')->orderBy('title')
        ->with(['children' => fn ($q) => $q->published()->orderBy('sort_order')])->get();
    $currentId = isset($page) ? $page->id : null;
@endphp
<nav class="lwk-docs-nav" aria-label="{{ __('larawebkit-front.docs.navigation') }}">
    @foreach ($nav as $section)
        <div class="lwk-docs-group">
            <a href="{{ $section->publicPath() }}" class="lwk-docs-group-title" @if ($section->id === $currentId) aria-current="page" @endif>{{ $section->title }}</a>
            @if ($section->children->isNotEmpty())
                <ul role="list">
                    @foreach ($section->children as $child)
                        <li><a href="{{ $child->publicPath() }}" @if ($child->id === $currentId) aria-current="page" @endif>{{ $child->title }}</a></li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endforeach
</nav>
