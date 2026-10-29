<?php

namespace App\Modules\Logs\Concerns;

use App\Modules\Logs\AuditLogger;
use App\Modules\Logs\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Add to any admin-managed model to record created/updated/deleted/restored
 * events with old and new values. Recipe: docs/recipes/new-audit-logged-model.md
 *
 * Optional on the model:
 *   protected array $auditExclude = ['secret_column'];
 *   public function auditLabel(): string { return $this->title; }
 */
trait Auditable
{
    public const AUDIT_ALWAYS_EXCLUDED = [
        'password', 'remember_token', 'app_authentication_secret', 'app_authentication_recovery_codes',
        'created_at', 'updated_at',
    ];

    public const AUDIT_MAX_VALUE_LENGTH = 20000;

    public static function bootAuditable(): void
    {
        static::created(fn (Model $model) => AuditLogger::record('created', $model, [], $model->auditValues($model->getAttributes())));

        static::updated(function (Model $model) {
            $changes = $model->auditValues($model->getChanges());
            if ($changes === []) {
                return;
            }
            $old = array_intersect_key($model->auditValues($model->getPrevious()), $changes);
            AuditLogger::record('updated', $model, $old, $changes);
        });

        static::deleted(function (Model $model) {
            $event = method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting() ? 'trashed' : 'deleted';
            AuditLogger::record($event, $model, $model->auditValues($model->getAttributes()));
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(fn (Model $model) => AuditLogger::record('restored', $model));
        }
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable', 'auditable_type', 'auditable_id')->latest('created_at');
    }

    /** @param  array<string, mixed>  $values */
    public function auditValues(array $values): array
    {
        $exclude = array_merge(self::AUDIT_ALWAYS_EXCLUDED, property_exists($this, 'auditExclude') ? $this->auditExclude : [], $this->getHidden());

        $out = [];
        foreach ($values as $key => $value) {
            if (in_array($key, $exclude, true)) {
                continue;
            }
            if (is_string($value) && mb_strlen($value) > self::AUDIT_MAX_VALUE_LENGTH) {
                $value = mb_substr($value, 0, self::AUDIT_MAX_VALUE_LENGTH).'…';
            }
            $out[$key] = $value;
        }

        return $out;
    }
}
