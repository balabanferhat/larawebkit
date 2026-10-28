<x-filament-widgets::widget>
    <x-filament::section :heading="__('larawebkit-dashboard.recent_audit')">
        <ul class="lwk-small" style="display:grid;gap:.35rem">
            @forelse ($audit as $log)
                <li>
                    <strong>{{ $log->user?->name ?? __('larawebkit-logs.system') }}</strong>
                    {{ mb_strtolower(__('larawebkit-logs.audit.events.'.$log->event)) }}
                    {{ class_basename(\Illuminate\Database\Eloquent\Relations\Relation::getMorphedModel($log->auditable_type) ?? $log->auditable_type) }}
                    “{{ \Illuminate\Support\Str::limit($log->auditable_label, 40) }}”
                    <span class="lwk-muted">· {{ $log->created_at->diffForHumans() }}</span>
                </li>
            @empty
                <li class="lwk-muted">{{ __('larawebkit-logs.ai_report.empty') }}</li>
            @endforelse
        </ul>
        @if ($failed->isNotEmpty())
            <h4 class="lwk-small lwk-muted" style="margin-top:1rem;font-weight:600">{{ __('larawebkit-dashboard.failed_logins') }}</h4>
            <ul class="lwk-small" style="display:grid;gap:.25rem">
                @foreach ($failed as $log)
                    <li>{{ $log->email ?? '—' }} · {{ $log->ip_address }} <span class="lwk-muted">· {{ $log->created_at->diffForHumans() }}</span></li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
