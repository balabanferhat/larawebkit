<?php

namespace App\Modules\Seo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Throwable;

class NotFoundLog extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['last_seen_at' => 'datetime'];
    }

    public static function hit(Request $request): void
    {
        try {
            $path = mb_substr('/'.ltrim($request->path(), '/'), 0, 500);
            $log = static::firstOrNew(['path' => $path]);
            $log->hits = ($log->hits ?? 0) + 1;
            $log->last_seen_at = now();
            $referrer = (string) $request->headers->get('referer');
            $log->last_referrer = $referrer !== '' ? mb_substr($referrer, 0, 500) : $log->last_referrer;
            $log->save();
        } catch (Throwable) {
            // Never let logging break the 404 page.
        }
    }
}
