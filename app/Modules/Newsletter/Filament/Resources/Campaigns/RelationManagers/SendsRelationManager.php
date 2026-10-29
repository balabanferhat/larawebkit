<?php

namespace App\Modules\Newsletter\Filament\Resources\Campaigns\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/** The send log: one row per recipient with status and error. */
class SendsRelationManager extends RelationManager
{
    protected static string $relationship = 'sends';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('larawebkit-newsletter.send_log');
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('id')
            ->columns([
                TextColumn::make('email')->label('E-mail')->searchable(),
                TextColumn::make('status')->label(__('larawebkit-newsletter.fields.status'))->badge()
                    ->color(fn (string $state) => ['sent' => 'success', 'failed' => 'danger', 'pending' => 'gray', 'skipped' => 'warning'][$state] ?? 'gray')
                    ->formatStateUsing(fn (string $state) => __("larawebkit-newsletter.send_status.$state")),
                TextColumn::make('sent_at')->label(__('larawebkit-newsletter.fields.sent_at'))->dateTime()->placeholder('—'),
                TextColumn::make('error')->label(__('larawebkit-newsletter.fields.error'))->limit(80)->placeholder('—'),
            ])
            ->filters([SelectFilter::make('status')->options(collect(['pending', 'sent', 'failed', 'skipped'])->mapWithKeys(fn ($s) => [$s => __("larawebkit-newsletter.send_status.$s")])->all())]);
    }
}
