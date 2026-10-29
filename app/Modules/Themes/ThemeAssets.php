<?php

namespace App\Modules\Themes;

use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\File;
use Illuminate\Support\HtmlString;
use RuntimeException;
use Throwable;

/**
 * Theme assets are prebuilt and committed (themes/{name}/assets), so production
 * needs no Node. `larawebkit:themes:build` bundles src/theme.css (resolving
 *
 * @import, minifying) into assets/theme.css - plain PHP, no toolchain. On
 * activation the assets are copied to public/themes/{name} for the web server;
 * until then a PHP route serves them from the theme folder.
 */
class ThemeAssets
{
    public function __construct(private ThemeManager $themes, private Vite $vite) {}

    public function styles(): HtmlString
    {
        $html = '';
        if ($css = $this->url('theme.css')) {
            $html .= '<link rel="stylesheet" href="'.e($css).'">';
        }
        if ($js = $this->url('theme.js')) {
            $html .= '<script src="'.e($js).'" defer></script>';
        }

        return new HtmlString($html.$this->projectAssets());
    }

    /** Public URL of an asset, looked up through the active theme chain. */
    public function url(string $file): ?string
    {
        foreach ($this->themes->chain() as $theme) {
            $source = $theme->assetPath($file);
            if (! is_file($source)) {
                continue;
            }
            $version = substr(md5((string) filemtime($source).filesize($source)), 0, 10);
            $published = $this->publicPath($theme->name, $file);
            if (is_file($published) && filesize($published) === filesize($source) && filemtime($published) >= filemtime($source)) {
                return asset($this->publicPrefix()."/{$theme->name}/$file").'?v='.$version;
            }

            return route('larawebkit.theme-asset', ['theme' => $theme->name, 'path' => $file, 'v' => $version]);
        }

        return null;
    }

    /** Copy assets/ and the screenshot to public/themes/{name}. */
    public function publish(Theme $theme): bool
    {
        try {
            $target = $this->publicPath($theme->name);
            File::ensureDirectoryExists($target);
            if (File::isDirectory($theme->assetPath())) {
                File::copyDirectory($theme->assetPath(), $target);
            }
            if ($shot = $theme->screenshot()) {
                File::copy($shot, $target.'/'.basename($shot));
            }

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }

    /** Bundle src/theme.css (and its @imports) into assets/theme.css. Returns the CSS. */
    public function build(Theme $theme, bool $write = true): ?string
    {
        $entry = $theme->path.'/src/theme.css';
        if (! is_file($entry)) {
            return null;
        }
        $css = '/* '.$theme->name.' '.$theme->version().' - built by php artisan larawebkit:themes:build from src/; do not edit */'."\n"
            .self::minify($this->bundle($entry));
        if ($write) {
            File::ensureDirectoryExists($theme->assetPath());
            File::put($theme->assetPath('theme.css'), $css."\n");
        }

        return $css."\n";
    }

    /** Resolve @import "./x.css", "../../other/src/x.css" or "theme:other/src/x.css". */
    public function bundle(string $file, array $seen = []): string
    {
        $real = realpath($file);
        if ($real === false) {
            throw new RuntimeException("CSS import not found: $file");
        }
        if (isset($seen[$real]) || count($seen) > 20) {
            return '';
        }
        $seen[$real] = true;
        $css = (string) file_get_contents($real);

        return (string) preg_replace_callback('/@import\s+(?:url\()?["\']([^"\']+)["\']\)?\s*;/', function (array $m) use ($real, $seen) {
            $target = str_starts_with($m[1], 'theme:')
                ? ThemeManager::path(substr($m[1], 6))
                : dirname($real).'/'.$m[1];
            if (! str_ends_with($target, '.css')) {
                return $m[0];
            }

            return $this->bundle($target, $seen);
        }, $css);
    }

    public static function minify(string $css): string
    {
        $css = (string) preg_replace('/\/\*.*?\*\//s', '', $css);
        $css = (string) preg_replace('/\s+/', ' ', $css);
        // Spaces around ":" are kept: ".a :hover" and ".a:hover" mean different things.
        $css = (string) preg_replace('/\s*([{};,>])\s*/', '$1', $css);

        return trim(str_replace(';}', '}', $css));
    }

    public function publicPath(string $theme, string $file = ''): string
    {
        return rtrim(public_path($this->publicPrefix()."/$theme/$file"), '/');
    }

    private function publicPrefix(): string
    {
        return trim((string) config('larawebkit-themes.public_path', 'themes'), '/');
    }

    /** A project's own Tailwind/Vite bundle (resources/css/app.css), when built. */
    private function projectAssets(): string
    {
        if (! is_file(public_path('build/manifest.json')) && ! is_file(public_path('hot'))) {
            return '';
        }
        try {
            return (string) $this->vite->__invoke(['resources/css/app.css']);
        } catch (Throwable) {
            return '';
        }
    }
}
