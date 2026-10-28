<?php

namespace App\Modules\Ai\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One row per AI call: no prompt or output text is stored. */
class AiUsage extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['succeeded' => 'boolean', 'cost_usd' => 'decimal:6', 'created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function spentThisMonth(): float
    {
        return (float) static::where('created_at', '>=', now()->startOfMonth())->sum('cost_usd');
    }
}
