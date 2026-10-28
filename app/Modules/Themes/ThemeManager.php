<?php

namespace App\Modules\Themes;

use Illuminate\Support\Facades\View;

/**
 * Front-end themes live in resources/views/themes/{name} with their CSS in
 * resources/css/themes/{name}.css. Any view a theme does not override falls
 * back to the "minimal" theme. Recipe: docs/recipes/new-theme.md
 */
class ThemeManager
{
    public const FALLBACK = 'minimal';

    /** @return array<string, array{label: string, description: string}> */
    public static function themes(): array
    {
        return [
            'minimal' => ['label' => 'larawebkit-themes.minimal.label', 'description' => 'larawebkit-themes.minimal.description'],
            'business' => ['label' => 'larawebkit-themes.business.label', 'description' => 'larawebkit-themes.business.description'],
            'docs' => ['label' => 'larawebkit-themes.docs.label', 'description' => 'larawebkit-themes.docs.description'],
        ];
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::themes())->map(fn ($t) => __($t['label']))->all();
    }

    public function active(): string
    {
        $theme = (string) setting('general.theme', self::FALLBACK);

        return array_key_exists($theme, self::themes()) ? $theme : self::FALLBACK;
    }

    /** Resolve "post" to "themes.{active}.post", falling back to the minimal theme. */
    public function view(string $name): string
    {
        $candidate = 'themes.'.$this->active().'.'.$name;

        return View::exists($candidate) ? $candidate : 'themes.'.self::FALLBACK.'.'.$name;
    }

    public function cssEntry(): string
    {
        return 'resources/css/themes/'.$this->active().'.css';
    }
}
