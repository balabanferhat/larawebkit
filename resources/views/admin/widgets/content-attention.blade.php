<x-filament-widgets::widget>
    <x-filament::section :heading="__('larawebkit-dashboard.attention')">
        @php($groups = ['pending' => $pending, 'drafts' => $drafts, 'scheduled' => $scheduled, 'weak' => $weak])
        @if (collect($groups)->every(fn ($g) => $g->isEmpty()))
            <p class="lwk-muted">{{ __('larawebkit-dashboard.all_clear') }}</p>
        @endif
        @foreach ($groups as $key => $items)
            @if ($items->isNotEmpty())
                <h4 class="lwk-small lwk-muted" style="margin-top:.75rem;font-weight:600">{{ __("larawebkit-dashboard.groups.$key") }}</h4>
                <ul class="lwk-small" style="display:grid;gap:.25rem">
                    @foreach ($items as $item)
                        <li>
                            <a href="{{ $editUrl($item) }}" style="text-decoration:underline">{{ $item->title }}</a>
                            <span class="lwk-muted">
                                @if ($key === 'scheduled') · {{ $item->published_at->isoFormat('LLL') }} @endif
                                @if ($key === 'weak') · SEO {{ $item->seo_score ?? '—' }} · {{ __('larawebkit-editor.panel.human') }} {{ $item->human_score ?? '—' }} @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        @endforeach
    </x-filament::section>
</x-filament-widgets::widget>
