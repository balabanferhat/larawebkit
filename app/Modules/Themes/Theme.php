<?php

namespace App\Modules\Themes;

/**
 * One theme folder: themes/{name}/ with theme.json, screenshot.webp, views/,
 * src/ (CSS sources), assets/ (prebuilt CSS/JS, committed) and lang/.
 */
final class Theme
{
    /** @param  array<string, mixed>  $manifest  decoded theme.json */
    public function __construct(
        public readonly string $name,
        public readonly string $path,
        public readonly array $manifest,
    ) {}

    public function parent(): ?string
    {
        $parent = $this->manifest['parent'] ?? null;

        return is_string($parent) && $parent !== '' && $parent !== $this->name ? $parent : null;
    }

    public function label(): string
    {
        $key = "theme-{$this->name}::theme.name";

        return __($key) !== $key ? __($key) : (string) ($this->manifest['name'] ?? $this->name);
    }

    public function description(): string
    {
        $key = "theme-{$this->name}::theme.description";

        return __($key) !== $key ? __($key) : (string) ($this->manifest['description'] ?? '');
    }

    public function version(): string
    {
        return (string) ($this->manifest['version'] ?? '0.0.0');
    }

    public function author(): string
    {
        return (string) ($this->manifest['author'] ?? '');
    }

    /** @return list<string>|null block types the theme supports; null = all */
    public function blocks(): ?array
    {
        $blocks = $this->manifest['supports']['blocks'] ?? '*';

        return is_array($blocks) ? array_values(array_map('strval', $blocks)) : null;
    }

    /** @return array<string, mixed> design token defaults declared by this theme only */
    public function defaults(): array
    {
        return (array) ($this->manifest['defaults'] ?? []);
    }

    public function viewsPath(): string
    {
        return $this->path.'/views';
    }

    public function langPath(): string
    {
        return $this->path.'/lang';
    }

    public function assetPath(string $file = ''): string
    {
        return rtrim($this->path.'/assets/'.$file, '/');
    }

    public function screenshot(): ?string
    {
        foreach (['screenshot.webp', 'screenshot.png', 'screenshot.jpg'] as $file) {
            if (is_file($this->path.'/'.$file)) {
                return $this->path.'/'.$file;
            }
        }

        return null;
    }
}
