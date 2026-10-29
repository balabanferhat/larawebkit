<?php

use App\Modules\Logs\Models\AuthLog;
use App\Modules\Security\Filament\Pages\ActiveSessions;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

it('enables TOTP two-factor authentication on the panel', function () {
    expect(Filament::getPanel('admin')->hasMultiFactorAuthentication())->toBeTrue();
});

it('logs enabling and disabling 2FA', function () {
    $user = editor();
    $user->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');
    $user->saveAppAuthenticationSecret(null);

    expect(AuthLog::where('user_id', $user->id)->pluck('event')->all())
        ->toContain('two_factor_enabled', 'two_factor_disabled');
});

it('stores the 2FA secret encrypted and hidden', function () {
    $user = editor();
    $user->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');

    $raw = DB::table('users')->where('id', $user->id)->value('app_authentication_secret');
    expect($raw)->not->toBe('JBSWY3DPEHPK3PXP')
        ->and($user->fresh()->toArray())->not->toHaveKey('app_authentication_secret');
});

it('lists sessions and logs out other devices', function () {
    config(['session.driver' => 'database']);
    $user = editor();
    DB::table('sessions')->insert([
        ['id' => 'other-device', 'user_id' => $user->id, 'ip_address' => '10.0.0.2', 'user_agent' => 'Mozilla/5.0 (Windows NT 10.0) Chrome/120', 'payload' => '', 'last_activity' => time()],
    ]);
    $this->actingAs($user);

    Livewire::test(ActiveSessions::class)
        ->assertSee('Chrome · Windows')
        ->call('logoutOtherDevices', 'password');

    expect(DB::table('sessions')->where('id', 'other-device')->exists())->toBeFalse()
        ->and(AuthLog::where('event', 'other_devices_logged_out')->exists())->toBeTrue();
});

it('refuses to log out other devices with a wrong password', function () {
    $this->actingAs(editor());

    Livewire::test(ActiveSessions::class)->call('logoutOtherDevices', 'wrong')->assertHasErrors('password');
});
