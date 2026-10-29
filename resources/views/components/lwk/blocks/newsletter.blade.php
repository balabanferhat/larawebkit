@php($image = $media[$data['image'] ?? 0] ?? null)
@if (\App\Modules\Core\ModuleRegistry::enabled('newsletter') && setting('features.newsletter', false))
<div class="lwk-container">
    <div @class(['lwk-signup', 'has-image' => $variant === 'split' && $image])>
        @if ($variant === 'split' && $image)<div class="lwk-signup-media"><x-lwk.image :media="$image" sizes="(min-width: 960px) 40vw, 100vw" /></div>@endif
        <x-lwk.newsletter :title="$data['heading'] ?? null" :text="$data['text'] ?? null" :button="$data['button'] ?? null" />
    </div>
</div>
@endif
