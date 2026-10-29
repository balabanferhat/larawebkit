<?php

namespace App\Modules\Logs\Filament\Resources\AuthLogs;

use App\Modules\Logs\Filament\Resources\AuthLogs\Pages\ListAuthLogs;
use App\Modules\Logs\Models\AuthLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class AuthLogResource extends Resource
{
    protected static ?string $model = AuthLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?int $navigationSort = 81;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('larawebkit.nav.logs');
    }

    public static function getModelLabel(): string
    {
        return __('larawebkit-logs.auth.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('larawebkit-logs.auth.plural');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view logs') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label(__('larawebkit-logs.fields.time'))->dateTime()->sortable(),
                TextColumn::make('event')->label(__('larawebkit-logs.fields.event'))->badge()
                    ->formatStateUsing(fn (string $state) => __("larawebkit-logs.auth.events.$state"))
                    ->color(fn (string $state) => in_array($state, ['failed', 'lockout', 'two_factor_failed'], true) ? 'danger' : (in_array($state, ['login', 'two_factor_enabled'], true) ? 'success' : 'gray')),
                TextColumn::make('email')->label(__('larawebkit-logs.fields.email'))->searchable(),
                TextColumn::make('user.name')->label(__('larawebkit-logs.fields.user'))->placeholder('—'),
                TextColumn::make('ip_address')->label(__('larawebkit-logs.fields.ip'))->searchable(),
                TextColumn::make('user_agent')->label(__('larawebkit-logs.fields.user_agent'))->limit(50)->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('event')->label(__('larawebkit-logs.fields.event'))
                    ->options(collect(AuthLog::EVENTS)->mapWithKeys(fn ($e) => [$e => __("larawebkit-logs.auth.events.$e")])->all()),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListAuthLogs::route('/')];
    }
}
