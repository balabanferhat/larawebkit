@props(['toc', 'title' => null])
@if (! empty($toc))
    <nav class="lwk-toc" aria-labelledby="lwk-toc-title">
        <p id="lwk-toc-title" class="lwk-toc-title">{{ $title ?? __('larawebkit-front.toc') }}</p>
        <ol>
            @foreach ($toc as $item)
                <li class="lwk-toc-l{{ $item['level'] }}"><a href="#{{ $item['id'] }}">{{ $item['text'] }}</a></li>
            @endforeach
        </ol>
    </nav>
@endif
