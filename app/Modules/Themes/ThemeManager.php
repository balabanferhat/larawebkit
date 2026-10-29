<?php

namespace App\Modules\Themes;

use App\Modules\Settings\SettingsRepository;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\View;

/**
 * WordPress-style themes: every folder in themes/ with a theme.json is a theme
 * (discovered, no list to maintain). A theme may name a parent; views, assets
 * and design defaults resolve through the chain theme → parent → … so a child
 * theme only contains what it changes. "minimal" is the root of the bundled
 * themes and the fallback when the active theme is missing.
 * Recipe: docs/recipes/new-theme.md
 */
class ThemeManager
{
    public const FALLBACK = 'minimal';

    /** Neutral palettes used when no theme in the chain declares its own. */
    private const LIGHT = ['bg' => '#ffffff', 'bg_soft' => '#f5f6f8', 'surface' => '#ffffff', 'text' => '#161a20', 'muted' => '#59626e', 'border' => '#dfe3e8'];

    private const DARK = ['bg' => '#0e1014', 'bg_soft' => '#161920', 'surface' => '#1b1f27', 'text' => '#e9ebef', 'muted' => '#a4acb8', 'border' => '#2c323c'];

    private const BASE_DEFAULTS = ['primary' => '#3b3fd8', 'accent' => '#db2777', 'font_pair' => 'system', 'radius' => 'medium'];

    /** @var array<string, Theme>|null */
    private ?array $themes = null;

    private ?string $registered = null;

    public static function path(string $append = ''): string
    {
        $root = rtrim((string) config('larawebkit-themes.path', base_path('themes')), '/');

        return $append === '' ? $root : $root.'/'.ltrim($append, '/');
    }

    /** @return array<string, Theme> discovered themes, keyed by folder name */
    public function all(): array
    {
        if ($this->themes !== null) {
            return $this->themes;
        }

        $themes = [];
        foreach (glob(self::path('*/theme.json')) ?: [] as $manifest) {
            $dir = dirname($manifest);
            $name = basename($dir);
            $data = json_decode((string) file_get_contents($manifest), true);
            if (! preg_match('/^[a-z0-9][a-z0-9-]{0,39}$/', $name) || ! is_array($data)) {
                continue;
            }
            $themes[$name] = new Theme($name, $dir, $data);
            if (File::isDirectory($dir.'/lang')) {
                Lang::addNamespace('theme-'.$name, $dir.'/lang');
            }
        }
        ksort($themes);

        return $this->themes = $themes;
    }

    public function flush(): void
    {
        $this->themes = null;
        $this->registered = null;
    }

    public function get(string $name): ?Theme
    {
        return $this->all()[$name] ?? null;
    }

    public function exists(string $name): bool
    {
        return $this->get($name) !== null;
    }

    /** @return array<string, string> name => label, for selects */
    public function options(): array
    {
        return collect($this->all())->map(fn (Theme $t) => $t->label())->all();
    }

    public function active(): string
    {
        $theme = (string) setting('general.theme', self::FALLBACK);

        return $this->exists($theme) ? $theme : self::FALLBACK;
    }

    public function activeTheme(): ?Theme
    {
        return $this->get($this->active());
    }

    /**
     * The theme and its ancestors, nearest first. "minimal" always ends the chain
     * (when installed) so every view has a fallback.
     *
     * @return list<Theme>
     */
    public function chain(?string $name = null): array
    {
        $chain = [];
        $current = $this->get($name ?? $this->active());
        while ($current && ! isset($chain[$current->name]) && count($chain) < 8) {
            $chain[$current->name] = $current;
            $current = $current->parent() ? $this->get($current->parent()) : null;
        }
        if (! isset($chain[self::FALLBACK]) && ($root = $this->get(self::FALLBACK))) {
            $chain[self::FALLBACK] = $root;
        }

        return array_values($chain);
    }

    /** Make the theme active and publish its assets (and its parents'). */
    public function activate(string $name): void
    {
        abort_unless($this->exists($name), 404);
        app(SettingsRepository::class)->set('general.theme', $name);
        foreach ($this->chain($name) as $theme) {
            app(ThemeAssets::class)->publish($theme);
        }
        $this->registered = null;
    }

    /**
     * Default design tokens, child values overriding the parent's.
     *
     * @return array{primary: string, accent: string, font_pair: string, radius: string, light: array<string, string>, dark: array<string, string>}
     */
    public function defaults(?string $theme = null): array
    {
        $defaults = ['light' => self::LIGHT, 'dark' => self::DARK] + self::BASE_DEFAULTS;
        foreach (array_reverse($this->chain($theme)) as $t) {
            foreach ($t->defaults() as $key => $value) {
                $defaults[$key] = in_array($key, ['light', 'dark'], true) && is_array($value) ? array_merge($defaults[$key], $value) : $value;
            }
        }

        return $defaults;
    }

    public function supportsBlock(string $type): bool
    {
        $blocks = $this->activeTheme()?->blocks();

        return $blocks === null || in_array($type, $blocks, true);
    }

    /** Resolve "post" to "theme::post" (looked up through the theme chain). */
    public function view(string $name): string
    {
        $this->register();

        return 'theme::'.$name;
    }

    /**
     * Register the view namespaces: "theme::" searches the active chain,
     * "theme-{name}::" points at one theme (e.g. @extends('theme-minimal::layout')).
     */
    public function register(): void
    {
        $active = $this->active();
        if ($this->registered === $active) {
            return;
        }

        View::replaceNamespace('theme', array_map(fn (Theme $t) => $t->viewsPath(), $this->chain()));
        foreach ($this->all() as $theme) {
            View::replaceNamespace('theme-'.$theme->name, [$theme->viewsPath()]);
        }
        $this->registered = $active;
    }
}
