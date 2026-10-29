@props(['seo'])
@if (count($seo->breadcrumbs) > 1)
    <nav aria-label="{{ __('larawebkit-front.breadcrumbs') }}" class="lwk-breadcrumbs">
        <ol>
            @foreach ($seo->breadcrumbs as $crumb)
                <li>
                    @if ($loop->last)
                        <span aria-current="page">{{ $crumb['name'] }}</span>
                    @else
                        <a href="{{ $crumb['url'] }}">{{ $crumb['name'] }}</a>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
