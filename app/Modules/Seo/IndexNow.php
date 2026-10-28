<?php

namespace App\Modules\Seo;

use Illuminate\Support\Facades\Http;
use Throwable;

/** Optional IndexNow ping (INDEXNOW_KEY + setting) when content is published or updated. */
class IndexNow
{
    public function enabled(): bool
    {
        return filled(config('services.indexnow.key')) && (bool) setting('seo.indexnow_enabled', false) && app()->environment('production');
    }

    /** @param  list<string>  $urls */
    public function submit(array $urls): bool
    {
        if (! $this->enabled() || $urls === []) {
            return false;
        }
        $key = (string) config('services.indexnow.key');
        $host = parse_url(CanonicalUrl::for('/'), PHP_URL_HOST);

        try {
            return Http::timeout(10)->post((string) config('services.indexnow.endpoint'), [
                'host' => $host,
                'key' => $key,
                'keyLocation' => CanonicalUrl::for("/$key.txt"),
                'urlList' => array_values($urls),
            ])->successful();
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }
}
