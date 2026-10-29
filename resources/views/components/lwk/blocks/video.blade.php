@php($video = \App\Modules\Blocks\VideoEmbed::parse((string) ($data['url'] ?? '')))
@if ($video)
<div class="lwk-container">
    @include('components.lwk.blocks.partials.head', ['center' => true])
    <figure class="lwk-video-block">
        @include('components.lwk.blocks.partials.video', ['video' => $video, 'poster' => $media[$data['poster'] ?? 0] ?? null, 'title' => $data['heading'] ?? ($data['caption'] ?? ''), 'priority' => $first])
        @if (filled($data['caption'] ?? null))<figcaption>{{ $data['caption'] }}</figcaption>@endif
    </figure>
</div>
@endif
