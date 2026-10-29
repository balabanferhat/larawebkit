<?php

namespace App\Modules\Import;

use Illuminate\Support\Facades\DB;

/** Remote id → local id per source and kind (post, page, category, tag, user, media). */
final class ImportMap
{
    /** @return array{local_id: int, remote_modified: ?string}|null */
    public static function find(string $source, string $kind, string|int $remoteId): ?array
    {
        $row = DB::table('import_map')->where(['source' => $source, 'kind' => $kind, 'remote_id' => (string) $remoteId])->first();

        return $row ? ['local_id' => (int) $row->local_id, 'remote_modified' => $row->remote_modified] : null;
    }

    public static function put(string $source, string $kind, string|int $remoteId, int $localId, ?string $modified = null): void
    {
        DB::table('import_map')->updateOrInsert(
            ['source' => $source, 'kind' => $kind, 'remote_id' => (string) $remoteId],
            ['local_id' => $localId, 'remote_modified' => $modified, 'updated_at' => now(), 'created_at' => now()],
        );
    }
}
