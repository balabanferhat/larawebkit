<?php

namespace App\Modules\Members;

use App\Modules\Members\Http\AccountController;
use App\Modules\Members\Http\EnsureMember;
use App\Modules\Members\Http\EnsureMembersEnabled;
use App\Modules\Members\Http\MemberAuthController;
use App\Modules\Members\Models\Member;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Front-end members (optional: Settings → Features → Members). The guard, provider and
 * password broker are registered here, so dropping the module removes all of it.
 */
class MembersServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        config([
            'auth.guards.member' => ['driver' => 'session', 'provider' => 'members'],
            'auth.providers.members' => ['driver' => 'eloquent', 'model' => Member::class],
            'auth.passwords.members' => ['provider' => 'members', 'table' => 'member_password_reset_tokens', 'expire' => 60, 'throttle' => 60],
        ]);
    }

    public function boot(): void
    {
        $router = $this->app['router'];
        $router->aliasMiddleware('member', EnsureMember::class);
        $router->aliasMiddleware('members.enabled', EnsureMembersEnabled::class);

        Route::middleware(['web', 'members.enabled'])->prefix('account')->name('members.')->group(function () {
            Route::get('/login', [MemberAuthController::class, 'showLogin'])->name('login');
            Route::post('/login', [MemberAuthController::class, 'login'])->middleware('throttle:10,1')->name('login.submit');
            Route::post('/logout', [MemberAuthController::class, 'logout'])->name('logout');
            Route::get('/register', [MemberAuthController::class, 'showRegister'])->name('register');
            Route::post('/register', [MemberAuthController::class, 'register'])->middleware(['public-form'])->name('register.submit');
            Route::get('/forgot-password', [MemberAuthController::class, 'showForgot'])->name('password.request');
            Route::post('/forgot-password', [MemberAuthController::class, 'sendResetLink'])->middleware('throttle:5,1')->name('password.email');
            Route::get('/reset-password/{token}', [MemberAuthController::class, 'showReset'])->name('password.reset');
            Route::post('/reset-password', [MemberAuthController::class, 'reset'])->middleware('throttle:10,1')->name('password.update');
            Route::get('/verify/{id}/{hash}', [MemberAuthController::class, 'verify'])->middleware(['signed', 'throttle:10,1'])->whereNumber('id')->name('verification.verify');

            Route::middleware('member')->group(function () {
                Route::get('/verify', [MemberAuthController::class, 'verifyNotice'])->name('verification.notice');
                Route::post('/verify/resend', [MemberAuthController::class, 'resendVerification'])->middleware('throttle:3,1')->name('verification.send');
                Route::get('/', [AccountController::class, 'show'])->name('account');
                Route::put('/', [AccountController::class, 'update'])->name('account.update');
                Route::put('/password', [AccountController::class, 'password'])->name('account.password');
                Route::delete('/', [AccountController::class, 'destroy'])->name('account.destroy');
            });
        });
    }
}
