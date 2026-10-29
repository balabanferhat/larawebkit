<?php

namespace App\Modules\Events\Filament\Resources\Events\RelationManagers;

use App\Modules\Events\Models\Registration;
use App\Modules\Events\RegistrationExport;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class RegistrationsRelationManager extends RelationManager
{
    protected static string $relationship = 'registrations';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('larawebkit-events.registrations');
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label(__('larawebkit-events.fields.name'))->searchable(),
                TextColumn::make('email')->label(__('larawebkit-events.fields.email'))->searchable()->copyable(),
                TextColumn::make('status')->label(__('larawebkit-events.fields.status'))->badge()
                    ->color(fn (string $state) => $state === 'registered' ? 'success' : 'gray')
                    ->formatStateUsing(fn (string $state) => __("larawebkit-events.status.$state")),
                IconColumn::make('member_id')->label(__('larawebkit-events.fields.member'))->boolean()->state(fn (Registration $r) => $r->member_id !== null),
                TextColumn::make('created_at')->label(__('larawebkit-events.fields.registered_at'))->dateTime()->sortable(),
                TextColumn::make('link_sent_at')->label(__('larawebkit-events.fields.link_sent_at'))->since()->placeholder('—'),
            ])
            ->filters([SelectFilter::make('status')->options(['registered' => __('larawebkit-events.status.registered'), 'cancelled' => __('larawebkit-events.status.cancelled')])])
            ->headerActions([
                Action::make('export')->label(__('larawebkit-events.export'))->icon('heroicon-o-arrow-down-tray')->color('gray')
                    ->action(fn () => RegistrationExport::csv($this->getOwnerRecord())),
            ])
            ->recordActions([
                Action::make('cancel')->label(__('larawebkit-events.cancel_registration'))->icon('heroicon-o-x-circle')->color('gray')
                    ->visible(fn (Registration $r) => $r->status === 'registered')->requiresConfirmation()
                    ->action(fn (Registration $r) => $r->update(['status' => 'cancelled'])),
                DeleteAction::make(),
            ]);
    }
}
