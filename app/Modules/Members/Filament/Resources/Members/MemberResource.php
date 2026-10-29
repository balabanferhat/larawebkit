<?php

namespace App\Modules\Members\Filament\Resources\Members;

use App\Modules\Members\Events\MemberDeleting;
use App\Modules\Members\Filament\Resources\Members\Pages\ListMembers;
use App\Modules\Members\Models\Member;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

/** Front-end members (readers, customers): list, verify, deactivate, delete. Not admin accounts. */
class MemberResource extends Resource
{
    protected static ?string $model = Member::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static ?int $navigationSort = 55;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('larawebkit.nav.marketing');
    }

    public static function getModelLabel(): string
    {
        return __('larawebkit-members.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('larawebkit-members.plural');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email'];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('manage members') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label(__('larawebkit-members.fields.name'))->searchable()->sortable(),
                TextColumn::make('email')->label(__('larawebkit-members.fields.email'))->searchable()->copyable(),
                IconColumn::make('email_verified_at')->label(__('larawebkit-members.verified'))->boolean()->state(fn (Member $m) => $m->hasVerifiedEmail()),
                IconColumn::make('is_active')->label(__('larawebkit-members.active'))->boolean(),
                TextColumn::make('last_login_at')->label(__('larawebkit-members.last_login'))->since()->placeholder('—')->sortable(),
                TextColumn::make('created_at')->label(__('larawebkit-members.joined'))->date()->sortable(),
            ])
            ->filters([
                TernaryFilter::make('email_verified_at')->nullable()->label(__('larawebkit-members.verified')),
                TernaryFilter::make('is_active')->label(__('larawebkit-members.active')),
            ])
            ->recordActions([
                Action::make('verify')->label(__('larawebkit-members.mark_verified'))->icon('heroicon-o-check-badge')->color('gray')
                    ->visible(fn (Member $m) => ! $m->hasVerifiedEmail())->requiresConfirmation()->action(fn (Member $m) => $m->markEmailAsVerified()),
                Action::make('toggle')->label(fn (Member $m) => $m->is_active ? __('larawebkit-members.deactivate') : __('larawebkit-members.activate'))
                    ->icon('heroicon-o-no-symbol')->color('gray')->requiresConfirmation()->action(fn (Member $m) => $m->update(['is_active' => ! $m->is_active])),
                DeleteAction::make()->before(fn (Member $record) => event(new MemberDeleting($record))),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()->before(fn (Collection $records) => $records->each(fn (Member $m) => event(new MemberDeleting($m)))),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListMembers::route('/')];
    }
}
