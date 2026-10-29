<?php

namespace App\Modules\Logs;

use App\Modules\Core\ModuleRegistry;
use App\Modules\Logs\Models\AuditLog;
use Closure;
use Illuminate\Database\Eloquent\Model;

/**
 * Writes "who changed what" entries. Models opt in with the Auditable trait.
 */
class AuditLogger
{
    private static ?string $reason = null;

    private static bool $paused = false;

    /** Attach a reason (e.g. a publish-gate override) to every entry written inside $callback. */
    public static function withReason(string $reason, Closure $callback): mixed
    {
        $previous = self::$reason;
        self::$reason = $reason;
        try {
            return $callback();
        } finally {
            self::$reason = $previous;
        }
    }

    public static function withoutAuditing(Closure $callback): mixed
    {
        $previous = self::$paused;
        self::$paused = true;
        try {
            return $callback();
        } finally {
            self::$paused = $previous;
        }
    }

    public static function record(string $event, Model $model, array $old = [], array $new = [], ?string $reason = null): ?AuditLog
    {
        if (self::$paused || ! ModuleRegistry::enabled('logs')) {
            return null;
        }

        $request = app()->runningInConsole() && ! app()->runningUnitTests() ? null : request();

        return AuditLog::create([
            'event' => $event,
            'auditable_type' => $model->getMorphClass(),
            'auditable_id' => (string) $model->getKey(),
            'auditable_label' => method_exists($model, 'auditLabel') ? mb_substr((string) $model->auditLabel(), 0, 255) : null,
            'old_values' => $old ?: null,
            'new_values' => $new ?: null,
            'user_id' => auth()->id(),
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? (mb_substr((string) $request->userAgent(), 0, 500) ?: null) : null,
            'reason' => $reason ?? self::$reason,
            'created_at' => now(),
        ]);
    }
}
