<x-filament-panels::page>
    <div class="lwk-report">
        <x-filament::section :heading="__('larawebkit-logs.ai_report.bots')" :description="__('larawebkit-logs.ai_report.period', ['days' => $this->days])">
            @if (empty($bots))
                <p class="lwk-muted">{{ __('larawebkit-logs.ai_report.empty') }}</p>
            @else
                <table class="lwk-table">
                    <thead><tr><th>{{ __('larawebkit-logs.fields.bot') }}</th><th>{{ __('larawebkit-logs.ai_report.hits') }}</th><th>{{ __('larawebkit-logs.ai_report.pages') }}</th><th>{{ __('larawebkit-logs.ai_report.last_seen') }}</th></tr></thead>
                    <tbody>
                        @foreach ($bots as $row)
                            <tr><td>{{ $row['bot_name'] }}</td><td>{{ $row['hits'] }}</td><td>{{ $row['pages'] }}</td><td>{{ \Illuminate\Support\Carbon::parse($row['last_seen'])->diffForHumans() }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-filament::section>

        <x-filament::section :heading="__('larawebkit-logs.ai_report.top_pages')">
            @if (empty($pages))
                <p class="lwk-muted">{{ __('larawebkit-logs.ai_report.empty') }}</p>
            @else
                <table class="lwk-table">
                    <thead><tr><th>{{ __('larawebkit-logs.fields.bot') }}</th><th>{{ __('larawebkit-logs.fields.path') }}</th><th>{{ __('larawebkit-logs.ai_report.hits') }}</th><th>{{ __('larawebkit-logs.ai_report.last_seen') }}</th></tr></thead>
                    <tbody>
                        @foreach ($pages as $row)
                            <tr><td>{{ $row['bot_name'] }}</td><td><code>{{ $row['path'] }}</code></td><td>{{ $row['hits'] }}</td><td>{{ \Illuminate\Support\Carbon::parse($row['last_seen'])->diffForHumans() }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-filament::section>

        <x-filament::section :heading="__('larawebkit-logs.ai_report.referrals')">
            @if (empty($referrals))
                <p class="lwk-muted">{{ __('larawebkit-logs.ai_report.empty') }}</p>
            @else
                <table class="lwk-table">
                    <thead><tr><th>{{ __('larawebkit-logs.ai_report.assistant') }}</th><th>{{ __('larawebkit-logs.fields.referrer') }}</th><th>{{ __('larawebkit-logs.ai_report.visits') }}</th><th>{{ __('larawebkit-logs.ai_report.last_seen') }}</th></tr></thead>
                    <tbody>
                        @foreach ($referrals as $row)
                            <tr><td>{{ $row['assistant'] }}</td><td>{{ $row['referrer_host'] }}</td><td>{{ $row['visits'] }}</td><td>{{ \Illuminate\Support\Carbon::parse($row['last_seen'])->diffForHumans() }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
