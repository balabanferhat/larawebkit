<?php

namespace App\Modules\Core\Downloads;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController
{
    public function __invoke(Request $request): StreamedResponse
    {
        try {
            $path = Crypt::decryptString((string) $request->query('file'));
        } catch (DecryptException) {
            abort(404);
        }
        $disk = Storage::disk((string) config('larawebkit.download_disk', 'local'));
        abort_if(str_contains($path, '..') || ! $disk->exists($path), 404);

        $name = Str::limit(basename((string) $request->query('name', basename($path))), 120, '');

        return $disk->download($path, $name, ['X-Robots-Tag' => 'noindex', 'Cache-Control' => 'private, no-store']);
    }
}
