<?php

namespace App\Modules\Backup;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * One zip per backup: the database (database.sqlite or database.sql), the files of the configured
 * disks under files/{disk}/ and a manifest.json. Keeps the newest N archives (setting backup.keep)
 * locally and on the optional remote disk. Restore: docs/backups.md.
 */
final class BackupManager
{
    public const LAST_RUN_KEY = 'larawebkit.backup.last';

    public function __construct(private DatabaseDumper $dumper) {}

    /** @return array{name: string, size: int, files: int, remote: ?string, warnings: list<string>} */
    public function create(bool $withFiles = true, bool $upload = true): array
    {
        @set_time_limit(0);
        $disk = $this->disk();
        $directory = trim((string) config('larawebkit-backup.directory', 'backups'), '/');
        $disk->makeDirectory($directory);
        $name = Str::slug((string) setting('general.site_name', config('app.name'))) ?: 'site';
        $name = $name.'-'.now()->format('Y-m-d-His').'.zip';
        $path = $disk->path($directory.'/'.$name);
        $warnings = [];

        $dump = $disk->path($directory.'/.dump-'.Str::random(8));
        $zip = new ZipArchive;
        $open = false;
        try {
            $entry = $this->dumper->dump($dump);
            if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException("Cannot write $path.");
            }
            $open = true;
            $password = (string) config('larawebkit-backup.password');
            if ($password !== '') {
                $zip->setPassword($password);
            }
            $add = function (string $file, string $as) use ($zip, $password) {
                $zip->addFile($file, $as);
                if ($password !== '') {
                    $zip->setEncryptionName($as, ZipArchive::EM_AES_256);
                }
            };
            $add($dump, $entry);

            $count = 0;
            if ($withFiles) {
                foreach ((array) config('larawebkit-backup.include_disks', []) as $source) {
                    $count += $this->addDisk($source, $add);
                }
            }
            $zip->addFromString('manifest.json', (string) json_encode([
                'created_at' => now()->toIso8601String(),
                'app' => config('app.name'),
                'url' => config('app.url'),
                'database' => ['driver' => config('database.connections.'.config('database.default').'.driver'), 'file' => $entry],
                'files' => $count,
                'disks' => $withFiles ? array_values((array) config('larawebkit-backup.include_disks', [])) : [],
                'encrypted' => $password !== '',
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $open = false;
            if (! $zip->close()) {
                throw new RuntimeException('Cannot finish the archive: '.$zip->getStatusString());
            }
        } catch (Throwable $e) {
            if ($open) {
                try {
                    $zip->close();
                } catch (Throwable) {
                }
            }
            @unlink($path);
            $this->remember(['ok' => false, 'error' => $e->getMessage()]);
            throw $e;
        } finally {
            @unlink($dump);
        }

        $remote = null;
        if ($upload && filled(config('larawebkit-backup.remote_disk'))) {
            try {
                $remote = $this->upload($path, $name);
            } catch (Throwable $e) {
                $warnings[] = 'Remote copy failed: '.$e->getMessage();
                Log::warning('larawebkit backup: remote copy failed', ['error' => $e->getMessage()]);
            }
        }
        $this->prune();

        $result = ['name' => $name, 'size' => (int) filesize($path), 'files' => $count, 'remote' => $remote, 'warnings' => $warnings];
        $this->remember(['ok' => true] + $result);

        return $result;
    }

    /** @return list<array{name: string, size: int, date: int}> newest first */
    public function list(): array
    {
        $disk = $this->disk();
        $directory = trim((string) config('larawebkit-backup.directory', 'backups'), '/');

        return collect($disk->files($directory))
            ->filter(fn ($f) => str_ends_with($f, '.zip'))
            ->map(fn ($f) => ['name' => basename($f), 'size' => (int) $disk->size($f), 'date' => (int) $disk->lastModified($f)])
            ->sortByDesc(fn ($b) => [$b['date'], $b['name']])->values()->all();
    }

    public function path(string $name): ?string
    {
        if (! preg_match('/^[A-Za-z0-9._-]+\.zip$/', $name)) {
            return null; // no path traversal through the file name
        }
        $relative = trim((string) config('larawebkit-backup.directory', 'backups'), '/').'/'.$name;

        return $this->disk()->exists($relative) ? $this->disk()->path($relative) : null;
    }

    public function delete(string $name): bool
    {
        $path = $this->path($name);

        return $path !== null && @unlink($path);
    }

    /** Keep the newest backup.keep archives here and on the remote disk. */
    public function prune(): int
    {
        $keep = max(1, (int) setting('backup.keep', 7));
        $deleted = 0;
        foreach (array_slice($this->list(), $keep) as $old) {
            $deleted += (int) $this->delete($old['name']);
        }
        if (filled(config('larawebkit-backup.remote_disk'))) {
            try {
                $remote = Storage::disk((string) config('larawebkit-backup.remote_disk'));
                $dir = trim((string) config('larawebkit-backup.remote_directory'), '/');
                $files = collect($remote->files($dir))->filter(fn ($f) => str_ends_with($f, '.zip'))->sortDesc()->values();
                foreach ($files->slice($keep) as $file) {
                    $remote->delete($file);
                }
            } catch (Throwable $e) {
                Log::warning('larawebkit backup: remote prune failed', ['error' => $e->getMessage()]);
            }
        }

        return $deleted;
    }

    /** @return array<string, mixed>|null */
    public static function lastRun(): ?array
    {
        $last = Cache::get(self::LAST_RUN_KEY);

        return is_array($last) ? $last : null;
    }

    private function upload(string $path, string $name): string
    {
        $remote = Storage::disk((string) config('larawebkit-backup.remote_disk'));
        $target = trim(trim((string) config('larawebkit-backup.remote_directory'), '/').'/'.$name, '/');
        $stream = fopen($path, 'r');
        try {
            if (! $remote->writeStream($target, $stream)) {
                throw new RuntimeException('the disk refused the file');
            }
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        return config('larawebkit-backup.remote_disk').':'.$target;
    }

    private function addDisk(string $source, callable $add): int
    {
        $disk = Storage::disk($source);
        $exclude = $source === config('larawebkit-backup.disk') ? (array) config('larawebkit-backup.exclude', []) : ['livewire-tmp'];
        $count = 0;
        foreach ($disk->allFiles() as $file) {
            foreach ($exclude as $prefix) {
                if ($file === $prefix || str_starts_with($file, rtrim($prefix, '/').'/')) {
                    continue 2;
                }
            }
            if (str_starts_with(basename($file), '.git')) {
                continue;
            }
            $add($disk->path($file), 'files/'.$source.'/'.$file);
            $count++;
        }

        return $count;
    }

    private function disk(): Filesystem
    {
        return Storage::disk((string) config('larawebkit-backup.disk', 'local'));
    }

    /** @param  array<string, mixed>  $result */
    private function remember(array $result): void
    {
        Cache::forever(self::LAST_RUN_KEY, $result + ['at' => now()->toIso8601String()]);
    }
}
