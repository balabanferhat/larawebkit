<?php

namespace App\Modules\Themes\Console;

use App\Modules\Themes\ThemeAssets;
use App\Modules\Themes\ThemeManager;
use Illuminate\Console\Command;

class BuildThemesCommand extends Command
{
    protected $signature = 'larawebkit:themes:build {theme? : Only this theme} {--check : Fail if a committed assets/theme.css is out of date}';

    protected $description = 'Bundle each theme\'s src/theme.css into assets/theme.css (no Node needed).';

    public function handle(ThemeManager $themes, ThemeAssets $assets): int
    {
        $names = $this->argument('theme') ? [$this->argument('theme')] : array_keys($themes->all());
        $stale = [];
        foreach ($names as $name) {
            $theme = $themes->get($name);
            if (! $theme) {
                $this->components->error(__('larawebkit-themes.errors.not_found', ['theme' => $name]));

                return self::FAILURE;
            }
            if ($this->option('check')) {
                $built = $assets->build($theme, write: false);
                if ($built !== null && $built !== @file_get_contents($theme->assetPath('theme.css'))) {
                    $stale[] = $name;
                }

                continue;
            }
            if ($assets->build($theme) !== null) {
                $assets->publish($theme);
                $this->components->info(__('larawebkit-themes.console.built', ['theme' => $name, 'kb' => round(filesize($theme->assetPath('theme.css')) / 1024, 1)]));
            }
        }

        if ($stale !== []) {
            $this->components->error(__('larawebkit-themes.console.stale', ['themes' => implode(', ', $stale)]));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
