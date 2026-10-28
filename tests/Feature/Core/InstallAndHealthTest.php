<?php

use App\Models\User;
use App\Modules\Content\Models\Page;
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
    file_put_contents(config('larawebkit.heartbeat_file'), (string) time());

    $this->artisan('larawebkit:deploy-check')->expectsOutputToContain('PHP')->assertSuccessful();
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
