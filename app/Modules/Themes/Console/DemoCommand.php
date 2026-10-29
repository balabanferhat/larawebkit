<?php

namespace App\Modules\Themes\Console;

use App\Models\User;
use App\Modules\Content\Models\Page;
use App\Modules\Content\Models\Post;
use App\Modules\Themes\Demo\DemoBuilder;
use App\Modules\Themes\Demo\DemoImages;
use App\Modules\Themes\ThemeManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * php artisan larawebkit:demo business
 *
 * Activates a theme and fills the site with its demo content (themes/{name}/demo.php,
 * inherited from the parent theme when missing). Placeholder text in the site language,
 * generated images, no third-party content. Re-running replaces the previous demo.
 */
class DemoCommand extends Command
{
    protected $signature = 'larawebkit:demo
        {theme : Theme folder name, e.g. business, personal, magazine, docs}
        {--remove : Only remove the previous demo content}
        {--force : Run even if the site already has its own posts or pages}
        {--image-scale=1 : Scale generated images (tests use a small value)}';

    protected $description = 'Activate a theme and create its demo content (pages, posts, menus, images).';

    public function handle(ThemeManager $themes): int
    {
        if ($this->option('remove')) {
            $removed = DemoBuilder::clean();
            $this->components->info(__('larawebkit-themes.demo.removed', ['count' => $removed]));

            return self::SUCCESS;
        }

        $name = (string) $this->argument('theme');
        if (! $themes->exists($name)) {
            $this->components->error(__('larawebkit-themes.errors.not_found', ['theme' => $name]));

            return self::FAILURE;
        }
        $script = collect($themes->chain($name))->map(fn ($t) => $t->path.'/demo.php')->first(fn ($f) => is_file($f));
        if (! $script) {
            $this->components->error(__('larawebkit-themes.demo.missing', ['theme' => $name]));

            return self::FAILURE;
        }

        DemoBuilder::clean();
        if (! $this->option('force') && $this->hasOwnContent()) {
            $this->components->error(__('larawebkit-themes.demo.has_content'));

            return self::FAILURE;
        }

        $locale = (string) setting('general.default_locale', config('app.locale', 'en'));
        app()->setLocale($locale);
        // Pages and posts need an author: the first super-admin (or nobody on a bare install).
        if ($admin = User::role('super-admin')->orderBy('id')->first()) {
            Auth::setUser($admin);
        }

        $builder = app()->makeWith(DemoBuilder::class, [
            'theme' => $name,
            'locale' => $locale,
            'art' => new DemoImages((float) $this->option('image-scale')),
        ]);

        $this->components->task(__('larawebkit-themes.demo.creating', ['theme' => $themes->get($name)->label()]), function () use ($script, $builder, $themes, $name) {
            $themes->activate($name);
            (require $script)($builder);
            // Every demo says so in the announcement bar: the people, places and articles are made up.
            if (blank(setting('header.announcement'))) {
                $builder->settings(['header.announcement' => __('larawebkit-demo.notice'), 'header.announcement_url' => '']);
            }
            $builder->save();
        });
        Cache::flush();

        $this->components->info(__('larawebkit-themes.demo.done', ['url' => url('/')]));

        return self::SUCCESS;
    }

    /** Published posts or pages that are not the kit's default policy stubs. */
    private function hasOwnContent(): bool
    {
        return Post::query()->exists()
            || Page::query()->whereNotIn('slug', ['privacy-policy', 'cookie-policy'])->exists();
    }
}
