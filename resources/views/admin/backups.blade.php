<x-filament-panels::page>
    @php($last = $this->getLastRun())
    @php($backups = $this->getBackups())
    <x-filament::section :heading="__('larawebkit-backup.status')">
        <p>
            @if ($last && $last['ok'])
                {{ __('larawebkit-backup.last_ok', ['date' => \Illuminate\Support\Carbon::parse($last['at'])->diffForHumans()]) }}
                @if (! empty($last['remote']))· {{ __('larawebkit-backup.remote_copy', ['target' => $last['remote']]) }}@endif
            @elseif ($last)
                <strong class="text-danger-600">{{ __('larawebkit-backup.last_failed', ['date' => \Illuminate\Support\Carbon::parse($last['at'])->diffForHumans()]) }}</strong> {{ $last['error'] ?? '' }}
            @else
                {{ __('larawebkit-backup.never') }}
            @endif
        </p>
        @foreach (($last['warnings'] ?? []) as $warning)<p class="text-warning-600">{{ $warning }}</p>@endforeach
        <p class="lwk-muted">{{ __('larawebkit-backup.schedule_hint', ['frequency' => __('larawebkit-settings.options.'.setting('backup.frequency', 'daily')), 'keep' => setting('backup.keep', 7)]) }}</p>
    </x-filament::section>

    <x-filament::section :heading="__('larawebkit-backup.archives')">
        @if ($backups === [])
            <p class="lwk-muted">{{ __('larawebkit-backup.empty') }}</p>
        @else
            <table class="lwk-table">
                <thead><tr><th>{{ __('larawebkit-backup.file') }}</th><th>{{ __('larawebkit-backup.size') }}</th><th>{{ __('larawebkit-backup.date') }}</th><th></th></tr></thead>
                <tbody>
                    @foreach ($backups as $backup)
                        <tr>
                            <td><code>{{ $backup['name'] }}</code></td>
                            <td>{{ \Illuminate\Support\Number::fileSize($backup['size'], 1) }}</td>
                            <td>{{ \Illuminate\Support\Carbon::createFromTimestamp($backup['date'])->isoFormat('lll') }}</td>
                            <td style="text-align:right">{{ ($this->downloadAction)(['name' => $backup['name']]) }} &nbsp; {{ ($this->deleteAction)(['name' => $backup['name']]) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
        <p class="lwk-muted" style="margin-top:1rem">{{ __('larawebkit-backup.restore_hint') }}</p>
    </x-filament::section>
</x-filament-panels::page>
