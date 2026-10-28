<?php

namespace App\Modules\Security\Filament\Resources\Roles;

use App\Modules\Security\Filament\Resources\Roles\Pages\CreateRole;
use App\Modules\Security\Filament\Resources\Roles\Pages\EditRole;
use App\Modules\Security\Filament\Resources\Roles\Pages\ListRoles;
use App\Modules\Security\RolesAndPermissions;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;
use UnitEnum;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?int $navigationSort = 92;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('larawebkit.nav.system');
    }

    public static function getModelLabel(): string
    {
        return __('larawebkit-security.roles.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('larawebkit-security.roles.plural');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('manage roles') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return static::canViewAny() && $record->name !== 'super-admin';
    }

    public static function canDelete(Model $record): bool
    {
        return static::canViewAny() && ! array_key_exists($record->name, RolesAndPermissions::ROLES);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label(__('larawebkit-security.roles.name'))->required()->unique(ignoreRecord: true)->maxLength(100),
            TextInput::make('guard_name')->default('web')->hidden()->dehydratedWhenHidden(),
            CheckboxList::make('permissions')->label(__('larawebkit-security.roles.permissions'))
                ->relationship('permissions', 'name')
                ->getOptionLabelFromRecordUsing(fn ($record) => __('larawebkit-security.permissions.'.str_replace(' ', '_', $record->name)))
                ->columns(2)->bulkToggleable()->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('larawebkit-security.roles.name'))->badge()->searchable(),
                TextColumn::make('permissions_count')->label(__('larawebkit-security.roles.permissions'))->counts('permissions'),
                TextColumn::make('users_count')->label(__('larawebkit-security.users.plural'))->counts('users'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }
}
