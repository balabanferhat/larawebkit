@props(['location', 'label' => null])
@php($items = \App\Modules\Core\ModuleRegistry::enabled('content') ? \App\Modules\Content\Models\Menu::for($location) : collect())
@if ($items->isNotEmpty())
    <nav aria-label="{{ $label ?? __('larawebkit-front.menu') }}" {{ $attributes->merge(['class' => 'lwk-menu']) }}>
        <ul>
            @foreach ($items as $item)
                <li>
                    <a href="{{ $item['url'] }}" @if (rtrim(request()->getPathInfo(), '/') === rtrim($item['url'], '/')) aria-current="page" @endif @if ($item['new_tab']) target="_blank" rel="noopener" @endif>{{ $item['label'] }}</a>
                </li>
            @endforeach
        </ul>
    </nav>
@endif
