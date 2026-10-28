<?php

namespace App\Modules\Content\Models;

use App\Modules\Logs\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Redirect extends Model
{
    use Auditable;

    public const CACHE_KEY = 'larawebkit.redirects';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_automatic' => 'boolean', 'last_hit_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::saving(function (Redirect $r) {
            $r->from_path = self::normalize($r->from_path);
        });
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    public static function normalize(?string $path): string
    {
        $path = (string) parse_url((string) $path, PHP_URL_PATH);

        return '/'.trim($path, '/');
    }

    /** @return array{id: int, to: ?string, status: int}|null */
    public static function lookup(string $path): ?array
    {
        $map = Cache::rememberForever(self::CACHE_KEY, fn () => static::query()
            ->get(['id', 'from_path', 'to_url', 'status_code'])
            ->mapWithKeys(fn ($r) => [$r->from_path => ['id' => $r->id, 'to' => $r->to_url, 'status' => $r->status_code]])
            ->all());

        return $map[self::normalize($path)] ?? null;
    }

    public function auditLabel(): string
    {
        return $this->from_path.' → '.($this->to_url ?? '410');
    }
}
