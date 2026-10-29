<?php

namespace App\Modules\Logs\Models;

use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

class AccessLog extends Model
{
    use MassPrunable;

    public const UPDATED_AT = null;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['day' => 'date', 'created_at' => 'datetime'];
    }

    public function prunable()
    {
        $days = (int) setting('privacy.access_log_retention_days', 90);

        return static::where('created_at', '<', now()->subDays(max(1, $days)));
    }
}
