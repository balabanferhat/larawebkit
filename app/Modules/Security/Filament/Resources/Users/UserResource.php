<?php

namespace App\Modules\Security\Filament\Resources\Users;

use App\Models\User;
use App\Modules\Logs\Filament\RelationManagers\AuditLogsRelationManager;
use App\Modules\Media\Models\Media;
use App\Modules\Security\Filament\Resources\Users\Pages\CreateUser;
use App\Modules\Security\Filament\Resources\Users\Pages\EditUser;
use App\Modules\Security\Filament\Resources\Users\Pages\ListUsers;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Password;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?int $navigationSort = 91;

    protected static ?string $recordTitleAttribute = 'name';

    /** @return list<string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email'];
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('larawebkit.nav.system');
    }

    public static function getModelLabel(): string
    {
        return __('larawebkit-security.users.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('larawebkit-security.users.plural');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('manage admins') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return parent::canDelete($record) && $record->getKey() !== auth()->id();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('larawebkit-security.users.account'))->columns(2)->schema([
                TextInput::make('name')->label(__('larawebkit-security.users.name'))->required()->maxLength(255),
                TextInput::make('email')->label(__('larawebkit-security.users.email'))->email()->required()->unique(ignoreRecord: true),
                TextInput::make('password')->label(__('larawebkit-security.users.password'))->password()->revealable()
                    ->rule(Password::defaults())
                    ->required(fn (string $operation) => $operation === 'create')
                    ->dehydrated(fn (?string $state) => filled($state))
                    ->helperText(fn (string $operation) => $operation === 'edit' ? __('larawebkit-security.users.password_keep') : null),
                Select::make('roles')->label(__('larawebkit-security.users.roles'))->relationship('roles', 'name')->multiple()->preload()->required(),
                Toggle::make('is_active')->label(__('larawebkit-security.users.active'))->default(true)
                    ->disabled(fn (?User $record) => $record?->getKey() === auth()->id()),
                Select::make('locale')->label(__('larawebkit-security.users.locale'))->options(config('larawebkit.locales')),
            ]),
            Section::make(__('larawebkit-security.users.author_profile'))->description(__('larawebkit-security.users.author_profile_help'))->columns(2)->schema([
                TextInput::make('slug')->label(__('larawebkit-security.users.slug'))->alphaDash()->unique(ignoreRecord: true),
                TextInput::make('job_title')->label(__('larawebkit-security.users.job_title')),
                TextInput::make('expertise')->label(__('larawebkit-security.users.expertise'))->columnSpanFull(),
                Textarea::make('bio')->label(__('larawebkit-security.users.bio'))->rows(4)->columnSpanFull(),
                Select::make('avatar_media_id')->label(__('larawebkit-security.users.avatar'))
                    ->options(fn () => Media::query()->latest()->limit(50)->pluck('file_name', 'id')->all())->searchable(),
                TagsInput::make('same_as')->label(__('larawebkit-security.users.same_as'))->placeholder('https://')->helperText(__('larawebkit-security.users.same_as_help')),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('larawebkit-security.users.name'))->searchable()->sortable(),
                TextColumn::make('email')->label(__('larawebkit-security.users.email'))->searchable(),
                TextColumn::make('roles.name')->label(__('larawebkit-security.users.roles'))->badge(),
                IconColumn::make('is_active')->label(__('larawebkit-security.users.active'))->boolean(),
                IconColumn::make('two_factor')->label(__('larawebkit-security.users.two_factor'))->boolean()->state(fn (User $r) => $r->hasTwoFactor()),
                TextColumn::make('last_login_at')->label(__('larawebkit-security.users.last_login'))->since()->placeholder('—')->sortable(),
            ])
            ->filters([
                SelectFilter::make('roles')->label(__('larawebkit-security.users.roles'))->relationship('roles', 'name'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getRelations(): array
    {
        return [AuditLogsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
