<x-filament-panels::page>
    <x-filament::section :description="__('larawebkit-security.sessions.description')">
        @unless (\App\Modules\Security\Filament\Pages\ActiveSessions::usesDatabaseSessions())
            <p class="lwk-muted">{{ __('larawebkit-security.sessions.driver_notice') }}</p>
        @else
            <table class="lwk-table">
                <thead>
                    <tr>
                        <th>{{ __('larawebkit-security.sessions.device') }}</th>
                        <th>{{ __('larawebkit-security.sessions.ip') }}</th>
                        <th>{{ __('larawebkit-security.sessions.last_active') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->getSessions() as $session)
                        <tr wire:key="session-{{ $session['id'] }}">
                            <td>{{ $session['agent'] }}</td>
                            <td>{{ $session['ip'] }}</td>
                            <td>{{ $session['last_active']->diffForHumans() }}</td>
                            <td class="lwk-right">
                                @if ($session['current'])
                                    <x-filament::badge color="success">{{ __('larawebkit-security.sessions.this_device') }}</x-filament::badge>
                                @else
                                    <x-filament::link tag="button" color="danger" wire:click="revoke(@js($session['id']))">{{ __('larawebkit-security.sessions.revoke') }}</x-filament::link>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endunless
    </x-filament::section>
</x-filament-panels::page>
