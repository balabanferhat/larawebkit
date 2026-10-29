<?php

namespace App\Modules\Themes\Console;

use App\Modules\Themes\ThemeAssets;
use App\Modules\Themes\ThemeManager;
use Illuminate\Console\Command;

class PublishThemesCommand extends Command
{
    protected $signature = 'larawebkit:themes:publish';

    protected $description = 'Copy every theme\'s prebuilt assets to public/themes (run after deploying).';

    public function handle(ThemeManager $themes, ThemeAssets $assets): int
    {
        foreach ($themes->all() as $theme) {
            $ok = $assets->publish($theme);
            $ok ? $this->components->info(__('larawebkit-themes.console.published', ['theme' => $theme->name]))
                : $this->components->warn(__('larawebkit-themes.console.publish_failed', ['theme' => $theme->name]));
        }

        return self::SUCCESS;
    }
}
