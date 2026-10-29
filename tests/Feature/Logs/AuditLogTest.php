<?php

use App\Models\User;
use App\Modules\Logs\AuditLogger;
use App\Modules\Logs\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Modules\Logs\Models\AuditLog;
use Livewire\Livewire;

it('records create, update and delete with old and new values, user, ip and agent', function () {
    $admin = superAdmin();
    $this->actingAs($admin);
    request()->server->set('REMOTE_ADDR', '203.0.113.9');
    request()->headers->set('User-Agent', 'PestBrowser/1.0');

    $user = User::factory()->create(['name' => 'Before']);
    $user->update(['name' => 'After']);
    $user->delete();

    $logs = AuditLog::where('auditable_type', $user->getMorphClass())->where('auditable_id', $user->id)->orderBy('id')->get();
    expect($logs->pluck('event')->all())->toBe(['created', 'updated', 'deleted']);

    $update = $logs[1];
    expect($update->old_values)->toBe(['name' => 'Before'])
        ->and($update->new_values)->toBe(['name' => 'After'])
        ->and($update->user_id)->toBe($admin->id)
        ->and($update->ip_address)->toBe('203.0.113.9')
        ->and($update->user_agent)->toBe('PestBrowser/1.0');
});

it('never logs passwords or 2FA secrets', function () {
    $user = User::factory()->create();
    $user->update(['password' => 'new-secret-password']);
    $user->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');

    $all = AuditLog::all()->map(fn ($l) => json_encode([$l->old_values, $l->new_values]))->implode(' ');
    expect($all)->not->toContain('password')->not->toContain('app_authentication_secret');
});

it('attaches a reason to entries written inside withReason', function () {
    AuditLogger::withReason('Approved by legal', fn () => User::factory()->create());

    expect(AuditLog::latest('id')->first()->reason)->toBe('Approved by legal');
});

it('shows the audit log to editors and filters by event', function () {
    $this->actingAs(editor());
    $u = User::factory()->create();
    $u->update(['name' => 'Changed']);

    Livewire::test(ListAuditLogs::class)
        ->assertCanSeeTableRecords(AuditLog::where('event', 'updated')->get())
        ->filterTable('event', 'created')
        ->assertCanNotSeeTableRecords(AuditLog::where('event', 'updated')->get());
});

it('hides the audit log from authors', function () {
    $this->actingAs(author())->get('/admin/audit-logs')->assertForbidden();
});
