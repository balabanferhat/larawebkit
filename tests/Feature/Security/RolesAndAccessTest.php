<?php

use App\Models\User;
use App\Modules\Security\Filament\Resources\Roles\RoleResource;
use App\Modules\Security\Filament\Resources\Users\Pages\CreateUser;
use App\Modules\Security\Filament\Resources\Users\UserResource;
use App\Modules\Security\RolesAndPermissions;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

it('seeds the three roles idempotently', function () {
    app(RolesAndPermissions::class)->sync();
    app(RolesAndPermissions::class)->sync();

    expect(Role::pluck('name')->sort()->values()->all())->toBe(['author', 'editor', 'super-admin']);
    expect(Role::findByName('author')->hasPermissionTo('publish content'))->toBeFalse();
    expect(Role::findByName('editor')->hasPermissionTo('override publish gate'))->toBeTrue();
});

it('lets super admins do everything through Gate::before', function () {
    expect(superAdmin()->can('anything at all'))->toBeTrue();
});

it('only lets active users with a role into the panel', function () {
    $panel = Filament::getPanel('admin');

    expect(User::factory()->create()->canAccessPanel($panel))->toBeFalse()
        ->and(editor()->canAccessPanel($panel))->toBeTrue()
        ->and(editor(['is_active' => false])->canAccessPanel($panel))->toBeFalse();
});

it('shows the login page and redirects guests', function () {
    $this->get('/admin/login')->assertOk()->assertSee('password', false);
    $this->get('/admin')->assertRedirect('/admin/login');
});

it('restricts admin user and role management', function () {
    $this->actingAs(editor());
    expect(UserResource::canViewAny())->toBeFalse()->and(RoleResource::canViewAny())->toBeFalse();

    $this->actingAs(superAdmin());
    expect(UserResource::canViewAny())->toBeTrue();
});

it('creates an admin with a role', function () {
    $this->actingAs(superAdmin());

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Jane Editor',
            'email' => 'jane@example.com',
            'password' => 'a-Long-password-123',
            'roles' => [Role::findByName('editor')->id],
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $jane = User::where('email', 'jane@example.com')->first();
    expect($jane->hasRole('editor'))->toBeTrue()->and($jane->slug)->toBe('jane-editor');
});

it('does not let a super admin delete themselves', function () {
    $me = superAdmin();
    $this->actingAs($me);

    expect(UserResource::canDelete($me))->toBeFalse()
        ->and(UserResource::canDelete(editor()))->toBeTrue();
});
