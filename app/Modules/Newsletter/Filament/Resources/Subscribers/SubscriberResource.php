<?php

namespace App\Modules\Newsletter\Filament\Resources\Subscribers;

use App\Modules\Newsletter\Filament\Resources\Subscribers\Pages\ListSubscribers;
use App\Modules\Newsletter\Models\Subscriber;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class SubscriberResource extends Resource
{
    protected static ?string $model = Subscriber::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelopeOpen;

    protected static ?int $navigationSort = 61;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('larawebkit.nav.marketing');
    }

    public static function getModelLabel(): string
    {
        return __('larawebkit-mail.newsletter.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('larawebkit-mail.newsletter.plural');
    }

    public static function canViewAny(): bool
    {
        return (auth()->user()?->can('manage newsletter') ?? false) && setting('features.newsletter', false);
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
                TextColumn::make('email')->label(__('larawebkit-front.newsletter.email'))->searchable()->copyable(),
                TextColumn::make('locale')->badge(),
                TextColumn::make('confirmed_at')->label(__('larawebkit-mail.newsletter.confirmed'))->dateTime()->placeholder('—')->sortable(),
                TextColumn::make('unsubscribed_at')->label(__('larawebkit-mail.newsletter.unsubscribed'))->dateTime()->placeholder('—'),
                TextColumn::make('consent_version')->label(__('larawebkit-newsletter.consent.version'))->placeholder('—')
                    ->tooltip(fn (Subscriber $record) => $record->consent_text)->toggleable(),
                TextColumn::make('consented_at')->label(__('larawebkit-newsletter.consent.at'))->dateTime()->placeholder('—')->toggleable(),
                TextColumn::make('created_at')->label(__('larawebkit-logs.fields.time'))->since(),
            ])
            ->filters([TernaryFilter::make('confirmed_at')->nullable()->label(__('larawebkit-mail.newsletter.confirmed'))])
            ->recordActions([DeleteAction::make()])
            ->toolbarActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListSubscribers::route('/')];
    }
}
