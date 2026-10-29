<?php

use App\Models\User;
use App\Modules\Content\Models\Category;
use App\Modules\Content\Models\Page;
use App\Modules\Content\Models\Post;
use App\Modules\Core\Console\DeployCheckCommand;
use App\Modules\Core\LarawebkitServiceProvider;
use App\Modules\Settings\SettingsRepository;
use Illuminate\Console\Scheduling\Schedule;

it('installs non-interactively: settings, roles, default content, super admin', function () {
    $this->artisan('larawebkit:install', [
        '--site-name' => 'Acme Blog', '--locale' => 'tr', '--admin-name' => 'Owner',
        '--admin-email' => 'owner@example.com', '--admin-password' => 'a-very-long-password', '--skip-migrate' => true,
        '--no-interaction' => true,
    ])->assertSuccessful();

    $owner = User::where('email', 'owner@example.com')->sole();
    expect($owner->hasRole('super-admin'))->toBeTrue()
        ->and(setting('general.site_name'))->toBe('Acme Blog')
        ->and(setting('general.default_locale'))->toBe('tr')
        ->and(Page::where('slug', 'privacy-policy')->value('title'))->toBe('Gizlilik politikası');
});

it('rejects a weak admin password', function () {
    $this->artisan('larawebkit:install', ['--admin-email' => 'a@example.com', '--admin-password' => 'short', '--skip-migrate' => true, '--no-interaction' => true])
        ->assertFailed();
});

it('runs the deploy check', function () {
    // Hermetic: provide what a fresh clone may lack (storage link, cron heartbeat) and restore it afterwards.
    file_put_contents(config('larawebkit.heartbeat_file'), (string) time());
    $link = public_path('storage');
    $created = ! file_exists($link);
    if ($created) {
        mkdir($link); // a directory satisfies the check and needs no symlink rights (Windows)
    }

    try {
        $missing = array_filter(DeployCheckCommand::REQUIRED_EXTENSIONS, fn ($ext) => ! extension_loaded($ext));
        $this->artisan('larawebkit:deploy-check')
            ->expectsOutputToContain('PHP memory_limit is')
            ->assertExitCode($missing === [] ? 0 : 1);
    } finally {
        if ($created) {
            rmdir($link);
        }
    }
});

it('warns when memory_limit is below 256M', function () {
    $previous = ini_set('memory_limit', '200M');
    try {
        $this->artisan('larawebkit:deploy-check')->expectsOutputToContain('PHP memory_limit is 200M (256M recommended');
    } finally {
        ini_set('memory_limit', $previous);
    }
});

it('fails the deploy check in production without the storage link', function () {
    app()->detectEnvironment(fn () => 'production');
    config(['app.debug' => false, 'app.url' => 'https://example.com', 'larawebkit-media.disk' => 'public']);
    file_put_contents(config('larawebkit.heartbeat_file'), (string) time());
    $link = public_path('storage');
    $moved = file_exists($link) ? $link.'.bak-test' : null;
    if ($moved) {
        rename($link, $moved);
    }

    try {
        $this->artisan('larawebkit:deploy-check')->assertFailed();
    } finally {
        if ($moved) {
            rename($moved, $link);
        }
    }
});

it('writes a scheduler heartbeat and drains the queue from the scheduler', function () {
    $events = collect(app(Schedule::class)->events())->map(fn ($e) => $e->description ?: $e->command);

    expect($events->implode(' '))->toContain('larawebkit:heartbeat')->toContain('larawebkit:queue')->toContain('larawebkit:logs:prune')->toContain('larawebkit:content:announce');
});

it('builds URLs in the site language when running in the console', function () {
    app(SettingsRepository::class)->setMany(['general.default_locale' => 'tr', 'general.active_locales' => ['tr', 'en']]);
    app()->setLocale('en'); // config('app.locale'), what a console run starts with

    expect(blog_path())->toBe('/en/blog'); // the bug: an English prefix on a Turkish site

    LarawebkitServiceProvider::useSiteLocale();
    $post = Post::factory()->published()->create(['slug' => 'merhaba', 'locale' => 'tr']);
    $category = Category::create(['name' => 'Haber', 'slug' => 'haber']);
    expect(app()->getLocale())->toBe('tr')
        ->and(blog_path())->toBe('/blog')
        ->and(blog_path('', 'en'))->toBe('/en/blog')
        ->and(post_path('merhaba', 'en'))->toBe('/en/blog/merhaba')
        ->and($post->publicPath())->toBe('/blog/merhaba')
        ->and($category->publicPath())->toBe('/blog/category/haber')
        ->and($category->publicPath('en'))->toBe('/en/blog/category/haber')
        ->and(localized_path('/x', 'en'))->toBe('/en/x');
});
