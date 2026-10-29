<?php

namespace App\Modules\Security;

use App\Modules\Security\Http\Middleware\ProtectPublicForm;
use App\Modules\Security\Http\Middleware\SecurityHeaders;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class SecurityServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // super-admin can do everything; every other check goes through permissions.
        Gate::before(fn ($user) => method_exists($user, 'hasRole') && $user->hasRole('super-admin') ? true : null);

        $router = $this->app['router'];
        // Global, so the Filament panel (own middleware stack) gets the headers too.
        $this->app->make(Kernel::class)->pushMiddleware(SecurityHeaders::class);
        $router->aliasMiddleware('public-form', ProtectPublicForm::class);

    }
}
