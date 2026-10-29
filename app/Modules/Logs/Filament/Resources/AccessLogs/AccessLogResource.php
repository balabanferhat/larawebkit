<?php

namespace App\Modules\Logs\Filament\Resources\AccessLogs;

use App\Modules\Logs\Filament\Resources\AccessLogs\Pages\ListAccessLogs;
use App\Modules\Logs\Models\AccessLog;
use App\Modules\Logs\Support\AgentClassifier;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class AccessLogResource extends Resource
{
    protected static ?string $model = AccessLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static ?int $navigationSort = 82;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('larawebkit.nav.logs');
    }

    public static function getModelLabel(): string
    {
        return __('larawebkit-logs.access.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('larawebkit-logs.access.plural');
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
                TextColumn::make('method')->label(__('larawebkit-logs.fields.method'))->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('path')->label(__('larawebkit-logs.fields.path'))->searchable()->limit(60),
                TextColumn::make('status')->label(__('larawebkit-logs.fields.status'))->badge()->color(fn (int $state) => $state >= 500 ? 'danger' : ($state >= 400 ? 'warning' : 'success'))->sortable(),
                TextColumn::make('agent_class')->label(__('larawebkit-logs.fields.agent'))->badge()->formatStateUsing(fn (string $state) => __("larawebkit-logs.access.agents.$state")),
                TextColumn::make('bot_name')->label(__('larawebkit-logs.fields.bot'))->placeholder('—')->searchable(),
                TextColumn::make('referrer_host')->label(__('larawebkit-logs.fields.referrer'))->placeholder('—')->searchable(),
                TextColumn::make('duration_ms')->label(__('larawebkit-logs.fields.duration'))->suffix(' ms')->sortable(),
            ])
            ->filters([
                SelectFilter::make('agent_class')->label(__('larawebkit-logs.fields.agent'))->options([
                    AgentClassifier::HUMAN => __('larawebkit-logs.access.agents.human'),
                    AgentClassifier::BOT => __('larawebkit-logs.access.agents.bot'),
                    AgentClassifier::AI_BOT => __('larawebkit-logs.access.agents.ai_bot'),
                ]),
                SelectFilter::make('status')->label(__('larawebkit-logs.fields.status'))->options(fn () => AccessLog::query()->distinct()->orderBy('status')->pluck('status', 'status')->all()),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListAccessLogs::route('/')];
    }
}
