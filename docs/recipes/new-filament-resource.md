# Recipe: a new Filament (admin) resource

Filament 5 conventions as used in this repository.

## Files

```
app/Modules/<Module>/Filament/Resources/<Plural>/<Singular>Resource.php
app/Modules/<Module>/Filament/Resources/<Plural>/Pages/List<Plural>.php
app/Modules/<Module>/Filament/Resources/<Plural>/Pages/Create<Singular>.php
app/Modules/<Module>/Filament/Resources/<Plural>/Pages/Edit<Singular>.php
```

Start from `php artisan make:filament-resource <Model> --generate` and move the files into the module, or copy
`app/Modules/Content/Filament/Resources/Redirects/RedirectResource.php` (single "manage" page with modal forms).

In the resource:

- Labels via translations: `getModelLabel()`, `getPluralModelLabel()`, `getNavigationGroup()` returning
  `__('larawebkit.nav.content')` (groups: content, marketing, logs, system, account).
- Authorization: `canViewAny()` → `auth()->user()?->can('manage <things>')`; also `canEdit`/`canDelete` when needed.
  Add the permission in `App\Modules\Security\RolesAndPermissions` (super-admin gets everything automatically).
- If the model uses `Auditable`, add `AuditLogsRelationManager::class` to `getRelations()` for a "History" tab.
- Register it in the module's Filament plugin (`$panel->resources([...])`).

## Tests

```php
it('lists and creates things', function () {
    $this->actingAs(editor());
    Livewire::test(CreateThing::class)->fillForm([...])->call('create')->assertHasNoFormErrors();
    Livewire::test(ListThings::class)->assertCanSeeTableRecords(Thing::all());
});
it('hides things from authors', fn () => $this->actingAs(author())->get(ThingResource::getUrl())->assertForbidden());
```

`tests/Feature/Core/AdminSmokeTest.php` renders every registered resource automatically.
