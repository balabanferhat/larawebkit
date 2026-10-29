<?php

namespace App\Modules\Core\Downloads;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\URL;

/**
 * Expiring, signed links to private files (lead magnets, event files). The path is
 * encrypted in the URL and the signature stops anyone from changing it or the expiry.
 */
final class SignedDownload
{
    public static function url(string $path, string $name, ?int $hours = null): string
    {
        return URL::temporarySignedRoute('larawebkit.download', now()->addHours($hours ?? (int) config('larawebkit.download_link_hours', 72)), [
            'file' => Crypt::encryptString($path),
            'name' => $name,
        ]);
    }
}
