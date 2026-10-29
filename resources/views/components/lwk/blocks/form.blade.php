@php($view = \App\Modules\Blocks\BlockForms::view($data['form'] ?? 'contact'))
@php($image = $media[$data['image'] ?? 0] ?? null)
<div class="lwk-container">
    <div class="lwk-form-block">
        <div class="lwk-form-intro">
            @include('components.lwk.blocks.partials.head', ['introKey' => 'text'])
            @if ($image && $variant === 'split')<x-lwk.image :media="$image" sizes="(min-width: 960px) 40vw, 100vw" class="lwk-form-image" />@endif
        </div>
        <div class="lwk-form-wrap">@if ($view)@include($view, ['formKey' => $data['form'] ?? 'contact'])@endif</div>
    </div>
</div>
