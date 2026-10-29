<?php

namespace App\Modules\Backup;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use PDO;
use RuntimeException;

/**
 * Writes the database to one file without external tools (shared hosting often has no mysqldump):
 * SQLite through VACUUM INTO (a consistent copy), MySQL / MariaDB as plain SQL.
 */
final class DatabaseDumper
{
    /** @return string the file name inside the archive (database.sqlite or database.sql) */
    public function dump(string $target, ?Connection $connection = null): string
    {
        $connection ??= DB::connection();

        return match ($connection->getDriverName()) {
            'sqlite' => $this->sqlite($connection, $target),
            'mysql', 'mariadb' => $this->mysql($connection, $target),
            default => throw new RuntimeException('Backups support SQLite, MySQL and MariaDB; this database uses '.$connection->getDriverName().'.'),
        };
    }

    private function sqlite(Connection $connection, string $target): string
    {
        @unlink($target);
        if ($connection->transactionLevel() === 0) {
            $connection->statement('VACUUM INTO '.$connection->getPdo()->quote($target));

            return 'database.sqlite';
        }

        // VACUUM is not allowed inside a transaction: copy schema and rows table by table instead.
        $copy = new PDO('sqlite:'.$target);
        $copy->exec('PRAGMA journal_mode = OFF; BEGIN');
        $objects = $connection->select("SELECT type, name, sql FROM sqlite_master WHERE sql IS NOT NULL AND name NOT LIKE 'sqlite_%' ORDER BY CASE type WHEN 'table' THEN 0 ELSE 1 END");
        foreach ($objects as $object) {
            $copy->exec($object->sql);
            if ($object->type !== 'table') {
                continue;
            }
            $q = '"'.str_replace('"', '""', $object->name).'"';
            $insert = null;
            foreach ($connection->cursor("SELECT * FROM $q") as $row) {
                $row = (array) $row;
                $insert ??= $copy->prepare("INSERT INTO $q (".implode(', ', array_map(fn ($c) => '"'.str_replace('"', '""', $c).'"', array_keys($row))).') VALUES ('.implode(', ', array_fill(0, count($row), '?')).')');
                $insert->execute(array_values($row));
            }
        }
        $copy->exec('COMMIT');
        unset($copy);

        return 'database.sqlite';
    }

    private function mysql(Connection $connection, string $target): string
    {
        $pdo = $connection->getPdo();
        $out = fopen($target, 'w');
        fwrite($out, '-- larawebkit backup '.now()->toIso8601String()."\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n");
        $chunk = max(1, (int) config('larawebkit-backup.dump_chunk', 500));

        foreach ($connection->select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'") as $row) {
            $table = (string) array_values((array) $row)[0];
            $q = '`'.str_replace('`', '``', $table).'`';
            $create = (array) $connection->selectOne("SHOW CREATE TABLE $q");
            fwrite($out, "DROP TABLE IF EXISTS $q;\n".array_values($create)[1].";\n\n");

            for ($offset = 0; ; $offset += $chunk) {
                $rows = $connection->select("SELECT * FROM $q LIMIT $chunk OFFSET $offset");
                if ($rows === []) {
                    break;
                }
                $columns = '('.implode(', ', array_map(fn ($c) => '`'.str_replace('`', '``', $c).'`', array_keys((array) $rows[0]))).')';
                $values = array_map(fn ($r) => '('.implode(', ', array_map(fn ($v) => $v === null ? 'NULL' : (is_int($v) || is_float($v) ? (string) $v : $pdo->quote((string) $v)), (array) $r)).')', $rows);
                fwrite($out, "INSERT INTO $q $columns VALUES\n".implode(",\n", $values).";\n");
                if (count($rows) < $chunk) {
                    break;
                }
            }
            fwrite($out, "\n");
        }
        fwrite($out, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($out);

        return 'database.sql';
    }
}
