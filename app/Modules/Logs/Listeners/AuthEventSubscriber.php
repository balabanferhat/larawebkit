<?php

namespace App\Modules\Logs\Listeners;

use App\Models\User;
use App\Modules\Logs\Models\AuthLog;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\OtherDeviceLogout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\PasswordResetLinkSent;
use Illuminate\Events\Dispatcher;

class AuthEventSubscriber
{
    /** The auth log is about admin accounts; front-end members (guard "member") are not logged here. */
    private function isMember(object $event): bool
    {
        return ($event->guard ?? null) === 'member' || (isset($event->user) && ! $event->user instanceof User);
    }

    public function handleLogin(Login $event): void
    {
        if ($this->isMember($event)) {
            return;
        }
        if ($event->user instanceof User) {
            $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
        }
        AuthLog::record('login', $event->user->getAuthIdentifier(), $event->user->email ?? null, ['remember' => $event->remember]);
    }

    public function handleFailed(Failed $event): void
    {
        if ($this->isMember($event)) {
            return;
        }
        AuthLog::record('failed', $event->user?->getAuthIdentifier(), $event->credentials['email'] ?? null);
    }

    public function handleLockout(Lockout $event): void
    {
        AuthLog::record('lockout', null, $event->request->input('email'));
    }

    public function handleLogout(Logout $event): void
    {
        if ($event->user && ! $this->isMember($event)) {
            AuthLog::record('logout', $event->user->getAuthIdentifier(), $event->user->email ?? null);
        }
    }

    public function handleOtherDeviceLogout(OtherDeviceLogout $event): void
    {
        if ($this->isMember($event)) {
            return;
        }
        AuthLog::record('other_devices_logged_out', $event->user->getAuthIdentifier(), $event->user->email ?? null);
    }

    public function handlePasswordReset(PasswordReset $event): void
    {
        if ($this->isMember($event)) {
            return;
        }
        AuthLog::record('password_reset', $event->user->getAuthIdentifier(), $event->user->email ?? null);
    }

    public function handlePasswordResetLinkSent(PasswordResetLinkSent $event): void
    {
        if ($this->isMember($event)) {
            return;
        }
        AuthLog::record('password_reset_link', $event->user->getAuthIdentifier(), $event->user->email ?? null);
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'handleLogin',
            Failed::class => 'handleFailed',
            Lockout::class => 'handleLockout',
            Logout::class => 'handleLogout',
            OtherDeviceLogout::class => 'handleOtherDeviceLogout',
            PasswordReset::class => 'handlePasswordReset',
            PasswordResetLinkSent::class => 'handlePasswordResetLinkSent',
        ];
    }
}
