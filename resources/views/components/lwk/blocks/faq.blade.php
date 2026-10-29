@php($items = \App\Modules\Blocks\Types\Faq::questions($data))
<div class="lwk-container">
    <div class="lwk-faq-block">
        @include('components.lwk.blocks.partials.head', ['center' => $variant === 'accordion'])
        <div class="lwk-faq">
            @foreach ($items as $item)
                @if ($variant === 'columns')
                    <div class="lwk-faq-item"><h3>{{ $item['question'] }}</h3><p>{!! nl2br(e($item['answer'])) !!}</p></div>
                @else
                    <details @if ($loop->first) open @endif><summary>{{ $item['question'] }}</summary><p>{!! nl2br(e($item['answer'])) !!}</p></details>
                @endif
            @endforeach
        </div>
    </div>
</div>
