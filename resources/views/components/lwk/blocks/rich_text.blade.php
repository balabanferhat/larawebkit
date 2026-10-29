@php($rendered = \App\Modules\Themes\ContentRenderer::prepare(\App\Modules\Blocks\Types\RichText::html($data), PHP_INT_MAX))
<div class="lwk-container">
    <div class="lwk-prose">{!! $rendered['html'] !!}</div>
</div>
