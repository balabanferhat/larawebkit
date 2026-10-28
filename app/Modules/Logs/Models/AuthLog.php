<?php

namespace App\Modules\Logs\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuthLog extends Model
{
    public const UPDATED_AT = null;

    public const EVENTS = [
        'login', 'logout', 'failed', 'lockout', 'two_factor_challenge', 'two_factor_failed',
        'two_factor_enabled', 'two_factor_disabled', 'password_reset_link', 'password_reset', 'other_devices_logged_out',
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['meta' => 'array', 'created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(string $event, ?int $userId = null, ?string $email = null, array $meta = []): self
    {
        $request = request();

        return static::create([
            'event' => $event,
            'user_id' => $userId,
            'email' => $email !== null ? mb_substr($email, 0, 255) : null,
            'ip_address' => $request?->ip(),
            'user_agent' => mb_substr((string) $request?->userAgent(), 0, 500) ?: null,
            'meta' => $meta ?: null,
            'created_at' => now(),
        ]);
    }
}
