# Recipe: a new module

A module is a folder under `app/Modules/<Name>` that a project can remove with one line.

## Files

1. `app/Modules/<Name>/<Name>ServiceProvider.php` — routes, event listeners, middleware, commands, scheduled tasks.
   Register routes with `Route::middleware('web')->group(...)` inside `boot()`.
2. `app/Modules/<Name>/<Name>Plugin.php` (only if it has admin screens) — implements `Filament\Contracts\Plugin`
   and registers resources/pages/widgets in `register(Panel $panel)`. Copy `app/Modules/Contact/ContactPlugin.php`.
3. Add the module to `config/larawebkit.php`:
   ```php
   'bookings' => ['provider' => Modules\Bookings\BookingsServiceProvider::class, 'plugin' => Modules\Bookings\BookingsPlugin::class],
   ```
4. Migrations in `database/migrations` (prefix `2026_..._create_<name>_tables.php`), translations in
   `lang/en/larawebkit-<name>.php` and `lang/tr/larawebkit-<name>.php`, config in `config/larawebkit-<name>.php`.
5. Permissions: add them to `App\Modules\Security\RolesAndPermissions::PERMISSIONS` and to the roles that need them.

## Rules

- Talk to other modules through events (`Content\Events\ContentSaved`, …) or traits, not by calling their internals.
- Guard optional integrations: `ModuleRegistry::enabled('other')`, `filled(config('services.x.key'))`.
- Domain-specific features (medical, legal, …) are always modules, never core changes.

## Tests

`tests/Feature/<Name>/…Test.php`: routes, permissions (an author must not see admin-only screens),
the disabled state, and translations (the en/tr parity test runs automatically).
