<?php

namespace App\Providers\Filament;

use App\Modules\Core\ModuleRegistry;
use App\Modules\Security\Filament\Auth\Login;
use App\Modules\Themes\AdminBranding;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path(config('larawebkit.admin_path', 'admin'))
            ->login(Login::class)
            ->passwordReset()
            ->profile(isSimple: false)
            ->multiFactorAuthentication(
                [AppAuthentication::make()->recoverable()],
                isRequired: (bool) config('larawebkit-security.require_two_factor'),
            )
            ->brandName(fn () => setting('general.site_name', config('app.name')))
            // Same brand as the public site (Appearance): logo, favicon and primary colour.
            ->brandLogo(fn () => AdminBranding::logo())
            ->darkModeBrandLogo(fn () => AdminBranding::logo(dark: true))
            ->brandLogoHeight('2rem')
            ->favicon(fn () => AdminBranding::favicon())
            ->colors(fn () => AdminBranding::colors())
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->globalSearchFieldKeyBindingSuffix()
            ->navigationGroups([
                NavigationGroup::make(fn () => __('larawebkit.nav.content')),
                NavigationGroup::make(fn () => __('larawebkit.nav.appearance')),
                NavigationGroup::make(fn () => __('larawebkit.nav.marketing')),
                NavigationGroup::make(fn () => __('larawebkit.nav.logs'))->collapsed(),
                NavigationGroup::make(fn () => __('larawebkit.nav.system'))->collapsed(),
                NavigationGroup::make(fn () => __('larawebkit.nav.account'))->collapsed(),
            ])
            ->unsavedChangesAlerts()
            ->databaseNotifications(false)
            ->plugins(ModuleRegistry::adminPlugins())
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
