<?php

namespace App\Modules\Security\Filament\Auth;

use App\Modules\Logs\Models\AuthLog;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Validation\ValidationException;

/**
 * Filament login (rate limited to 5 attempts per minute by Filament) that also
 * writes two-factor challenge / failure events to the auth log.
 */
class Login extends BaseLogin
{
    public function authenticate(): ?LoginResponse
    {
        $challenged = filled($this->userUndertakingMultiFactorAuthentication);
        $user = $challenged ? $this->getUserUndertakingMultiFactorAuthentication() : null;

        try {
            $response = parent::authenticate();
        } catch (ValidationException $e) {
            if ($challenged && $user) {
                AuthLog::record('two_factor_failed', $user->getAuthIdentifier(), $user->email ?? null);
            }

            throw $e;
        }

        if (! $challenged && $response === null && filled($this->userUndertakingMultiFactorAuthentication)) {
            $pending = $this->getUserUndertakingMultiFactorAuthentication();
            AuthLog::record('two_factor_challenge', $pending?->getAuthIdentifier(), $pending->email ?? null);
        }

        return $response;
    }
}
