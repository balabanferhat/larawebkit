<?php

namespace App\Modules\Themes;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;
use ZipArchive;

/**
 * Installs a theme from a .zip (Appearance → Themes, super-admins only, off by
 * default). The archive must contain theme.json at its root or inside one top
 * folder. Paths are checked before anything is written: no absolute paths,
 * no "..", no symlinks, only whitelisted file types, size limits.
 */
class ThemeInstaller
{
    public function __construct(private ThemeManager $themes, private ThemeAssets $assets) {}

    public static function enabled(): bool
    {
        return (bool) config('larawebkit-themes.allow_upload') && class_exists(ZipArchive::class);
    }

    public function install(string $zipPath): Theme
    {
        if (! self::enabled()) {
            throw new InvalidArgumentException(__('larawebkit-themes.upload.disabled'));
        }
        $maxBytes = (int) config('larawebkit-themes.max_upload_mb', 20) * 1024 * 1024;
        if (filesize($zipPath) > $maxBytes) {
            throw new InvalidArgumentException(__('larawebkit-themes.upload.too_large', ['mb' => config('larawebkit-themes.max_upload_mb')]));
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw new InvalidArgumentException(__('larawebkit-themes.upload.invalid'));
        }

        try {
            [$prefix, $entries] = $this->inspect($zip, $maxBytes * 5);
            $manifest = json_decode((string) $zip->getFromName($prefix.'theme.json'), true);
            if (! is_array($manifest) || blank($manifest['name'] ?? null)) {
                throw new InvalidArgumentException(__('larawebkit-themes.upload.no_manifest'));
            }
            $slug = Str::slug((string) ($manifest['slug'] ?? ($prefix !== '' ? trim($prefix, '/') : $manifest['name'])));
            if (! preg_match('/^[a-z0-9][a-z0-9-]{0,39}$/', $slug)) {
                throw new InvalidArgumentException(__('larawebkit-themes.upload.invalid'));
            }
            if ($this->themes->exists($slug) || File::exists(ThemeManager::path($slug))) {
                throw new InvalidArgumentException(__('larawebkit-themes.upload.exists', ['theme' => $slug]));
            }

            $target = ThemeManager::path($slug);
            File::ensureDirectoryExists($target);
            foreach ($entries as $index => $relative) {
                $destination = $target.'/'.$relative;
                File::ensureDirectoryExists(dirname($destination));
                File::put($destination, (string) $zip->getFromIndex($index));
            }
        } finally {
            $zip->close();
        }

        $this->themes->flush();
        $theme = $this->themes->get($slug) ?? throw new InvalidArgumentException(__('larawebkit-themes.upload.invalid'));
        $this->assets->publish($theme);

        return $theme;
    }

    /** @return array{0: string, 1: array<int, string>} common prefix and index => relative path */
    private function inspect(ZipArchive $zip, int $maxUncompressed): array
    {
        $allowed = (array) config('larawebkit-themes.allowed_extensions', []);
        $names = [];
        $total = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $name = str_replace('\\', '/', (string) $stat['name']);
            if (str_ends_with($name, '/') || str_starts_with($name, '__MACOSX/') || basename($name) === '.DS_Store') {
                continue;
            }
            $zip->getExternalAttributesIndex($i, $os, $attributes);
            $isLink = $os === ZipArchive::OPSYS_UNIX && (($attributes >> 16) & 0170000) === 0120000;
            if ($isLink || str_starts_with($name, '/') || preg_match('#(^|/)\.\.(/|$)#', $name) || preg_match('/^[a-z]:/i', $name) || str_contains($name, "\0")) {
                throw new InvalidArgumentException(__('larawebkit-themes.upload.unsafe', ['file' => $name]));
            }
            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (! in_array($extension, $allowed, true)) {
                throw new InvalidArgumentException(__('larawebkit-themes.upload.file_type', ['file' => $name]));
            }
            $total += (int) $stat['size'];
            if ($total > $maxUncompressed) {
                throw new InvalidArgumentException(__('larawebkit-themes.upload.too_large', ['mb' => config('larawebkit-themes.max_upload_mb')]));
            }
            $names[$i] = $name;
        }

        // theme.json at the root, or every file inside a single top-level folder.
        $prefix = '';
        if (! in_array('theme.json', $names, true)) {
            $tops = array_unique(array_map(fn ($n) => explode('/', $n, 2)[0], $names));
            if (count($tops) !== 1 || ! in_array($tops[0].'/theme.json', $names, true)) {
                throw new InvalidArgumentException(__('larawebkit-themes.upload.no_manifest'));
            }
            $prefix = $tops[0].'/';
        }

        return [$prefix, array_map(fn ($n) => substr($n, strlen($prefix)), $names)];
    }
}
