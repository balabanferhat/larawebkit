<x-filament-widgets::widget>
    <div class="lwk-report" style="grid-template-columns: repeat(auto-fit, minmax(20rem, 1fr));">
        <x-filament::section :heading="__('larawebkit-dashboard.top_pages')" :description="__('larawebkit-dashboard.last_30_days')">
            @if (empty($pages))
                <p class="lwk-muted">{{ __('larawebkit-logs.ai_report.empty') }}</p>
            @else
                <table class="lwk-table">
                    <tbody>
                        @foreach ($pages as $row)
                            <tr><td><a href="{{ url($row['path']) }}" target="_blank" rel="noopener">{{ $row['path'] }}</a></td><td class="lwk-right">{{ number_format($row['views']) }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-filament::section>
        <x-filament::section :heading="__('larawebkit-dashboard.referrers')" :description="__('larawebkit-dashboard.referrers_help')">
            @if (empty($referrers))
                <p class="lwk-muted">{{ __('larawebkit-logs.ai_report.empty') }}</p>
            @else
                <table class="lwk-table">
                    <tbody>
                        @foreach ($referrers as $row)
                            <tr>
                                <td>{{ $row['host'] }} @if ($row['ai'])<x-filament::badge color="success" style="display:inline-flex">{{ $row['ai'] }}</x-filament::badge>@endif</td>
                                <td class="lwk-right">{{ number_format($row['visits']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-filament::section>
    </div>
</x-filament-widgets::widget>
