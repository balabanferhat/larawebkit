<?php

namespace App\Modules\Contact\Filament\Resources\ContactMessages;

use App\Modules\Contact\Filament\Resources\ContactMessages\Pages\ListContactMessages;
use App\Modules\Contact\Models\ContactMessage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class ContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    protected static ?int $navigationSort = 60;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('larawebkit.nav.marketing');
    }

    public static function getModelLabel(): string
    {
        return __('larawebkit-mail.contact.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('larawebkit-mail.contact.plural');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = ContactMessage::whereNull('read_at')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('manage contact messages') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('name')->label(__('larawebkit-front.contact.name')),
            TextEntry::make('email')->label(__('larawebkit-front.contact.email'))->copyable(),
            TextEntry::make('subject')->label(__('larawebkit-front.contact.subject'))->placeholder('—'),
            TextEntry::make('created_at')->label(__('larawebkit-logs.fields.time'))->dateTime(),
            TextEntry::make('message')->label(__('larawebkit-front.contact.message'))->columnSpanFull()->prose(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                IconColumn::make('read_at')->label('')->boolean()->state(fn (ContactMessage $r) => $r->read_at !== null),
                TextColumn::make('name')->label(__('larawebkit-front.contact.name'))->searchable(),
                TextColumn::make('email')->label(__('larawebkit-front.contact.email'))->searchable(),
                TextColumn::make('subject')->label(__('larawebkit-front.contact.subject'))->limit(50)->placeholder('—'),
                TextColumn::make('created_at')->label(__('larawebkit-logs.fields.time'))->since()->sortable(),
            ])
            ->filters([TernaryFilter::make('read_at')->nullable()->label(__('larawebkit-mail.contact.read'))])
            ->recordActions([
                ViewAction::make()->after(fn (ContactMessage $record) => $record->read_at ?? $record->update(['read_at' => now()])),
                Action::make('reply')->label(__('larawebkit-mail.contact.reply'))->icon('heroicon-o-envelope')
                    ->url(fn (ContactMessage $r) => 'mailto:'.$r->email.'?subject='.rawurlencode('Re: '.($r->subject ?? '')))->openUrlInNewTab(),
                DeleteAction::make(),
            ])
            ->toolbarActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListContactMessages::route('/')];
    }
}
