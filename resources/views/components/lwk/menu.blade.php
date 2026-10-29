{{-- Accessible navigation (WAI disclosure pattern): dropdowns open on hover/focus without JS and with a toggle button (click, touch, keyboard) when theme.js runs. --}}
@props(['location', 'label' => null, 'dropdowns' => true])
@php
    $items = \App\Modules\Core\ModuleRegistry::enabled('content') ? \App\Modules\Content\Models\Menu::for($location) : collect();
    $current = rtrim(request()->getPathInfo(), '/') ?: '/';
    $isCurrent = fn (?string $url) => $url !== null && (rtrim((string) parse_url($url, PHP_URL_PATH), '/') ?: '/') === $current && ! str_starts_with($url, 'http');
@endphp
@if ($items->isNotEmpty())
    <nav aria-label="{{ $label ?? \App\Modules\Content\Models\Menu::label($location) ?? __('larawebkit-front.menu') }}" {{ $attributes->merge(['class' => 'lwk-menu lwk-menu-'.$location]) }}>
        <ul class="lwk-menu-list">
            @foreach ($items as $i => $item)
                @php($sub = $dropdowns ? $item['children'] : [])
                @php($id = 'lwk-sub-'.$location.'-'.$i)
                @php($active = $isCurrent($item['url']) || collect($item['children'])->contains(fn ($c) => $isCurrent($c['url'])))
                <li @class(['lwk-menu-item', 'has-children' => $sub !== [], 'is-active' => $active])>
                    @if ($item['url'] !== null)
                        <a href="{{ $item['url'] }}" @if ($isCurrent($item['url'])) aria-current="page" @endif @if ($item['new_tab']) target="_blank" rel="noopener" @endif>{{ $item['label'] }}</a>
                    @endif
                    @if ($sub !== [])
                        <button type="button" class="lwk-sub-toggle {{ $item['url'] === null ? 'is-label' : '' }}" aria-expanded="false" aria-controls="{{ $id }}">
                            @if ($item['url'] === null){{ $item['label'] }}@else<span class="lwk-sr-only">{{ __('larawebkit-front.nav.submenu', ['label' => $item['label']]) }}</span>@endif
                            {{ \App\Modules\Themes\Support\Icons::ui('chevron-down') }}
                        </button>
                        <ul class="lwk-sub" id="{{ $id }}">
                            @foreach ($sub as $child)
                                <li><a href="{{ $child['url'] }}" @if ($isCurrent($child['url'])) aria-current="page" @endif @if ($child['new_tab']) target="_blank" rel="noopener" @endif>{{ $child['label'] }}</a></li>
                            @endforeach
                        </ul>
                    @endif
                </li>
            @endforeach
        </ul>
    </nav>
@endif
