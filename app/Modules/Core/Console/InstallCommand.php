<?php

namespace App\Modules\Core\Console;

use App\Models\User;
use App\Modules\Security\RolesAndPermissions;
use App\Modules\Settings\SettingsRepository;
use Database\Seeders\LarawebkitSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class InstallCommand extends Command
{
    protected $signature = 'larawebkit:install
        {--site-name= : Site name}
        {--locale= : Default language (en, tr)}
        {--admin-name= : First admin name}
        {--admin-email= : First admin e-mail}
        {--admin-password= : First admin password}
        {--skip-migrate : Do not run migrations}';

    protected $description = 'Set up the site: migrations, roles, default content and the first super-admin.';

    public function handle(SettingsRepository $settings): int
    {
        $this->components->info(__('larawebkit.install.welcome'));
        $interactive = $this->input->isInteractive();
        $locales = (array) config('larawebkit.locales');

        $siteName = $this->option('site-name') ?? ($interactive ? text(__('larawebkit.install.site_name'), default: (string) config('app.name'), required: true) : config('app.name'));
        $locale = $this->option('locale') ?? ($interactive ? select(__('larawebkit.install.locale'), $locales, default: 'en') : 'en');
        $name = $this->option('admin-name') ?? ($interactive ? text(__('larawebkit.install.admin_name'), default: 'Admin', required: true) : 'Admin');
        $email = $this->option('admin-email') ?? ($interactive ? text(__('larawebkit.install.admin_email'), required: true, validate: ['email' => 'required|email']) : null);
        $plain = $this->option('admin-password') ?? ($interactive ? password(__('larawebkit.install.admin_password'), required: true, validate: ['password' => 'required|min:12']) : null);

        $validator = Validator::make(compact('locale', 'email', 'plain'), [
            'locale' => ['required', 'in:'.implode(',', array_keys($locales))],
            'email' => ['required', 'email'],
            'plain' => ['required', 'string', 'min:12'],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        if (! $this->option('skip-migrate')) {
            $this->components->task(__('larawebkit.install.migrating'), fn () => $this->callSilently('migrate', ['--force' => true]) === 0);
        }

        app(RolesAndPermissions::class)->sync();
        $settings->setMany([
            'general.site_name' => $siteName,
            'general.default_locale' => $locale,
            'general.active_locales' => [$locale],
        ]);
        app()->setLocale($locale);
        $this->callSilently('db:seed', ['--class' => LarawebkitSeeder::class, '--force' => true]);

        $user = User::where('email', $email)->first();
        if ($user) {
            $this->components->warn(__('larawebkit.install.admin_exists'));
        } else {
            $user = User::create(['name' => $name, 'email' => $email, 'password' => $plain, 'locale' => $locale]);
            $user->forceFill(['email_verified_at' => now()])->save();
        }
        $user->syncRoles(['super-admin']);

        $this->components->info(__('larawebkit.install.done', ['url' => url(config('larawebkit.admin_path', 'admin'))]));

        return self::SUCCESS;
    }
}
