<?php

namespace App\Modules\Logs\Filament\Resources\AuditLogs;

use App\Modules\Logs\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Modules\Logs\Filament\Resources\AuditLogs\Pages\ViewAuditLog;
use App\Modules\Logs\Models\AuditLog;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use UnitEnum;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?int $navigationSort = 80;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('larawebkit.nav.logs');
    }

    public static function getModelLabel(): string
    {
        return __('larawebkit-logs.audit.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('larawebkit-logs.audit.plural');
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

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return static::auditTable($table, withModel: true);
    }

    /** Shared with the per-record "History" relation manager. */
    public static function auditTable(Table $table, bool $withModel = true): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns(array_filter([
                TextColumn::make('created_at')->label(__('larawebkit-logs.fields.time'))->dateTime()->sortable(),
                TextColumn::make('event')->label(__('larawebkit-logs.fields.event'))->badge()
                    ->formatStateUsing(fn (string $state) => __("larawebkit-logs.audit.events.$state"))
                    ->color(fn (string $state) => match ($state) {
                        'created' => 'success', 'deleted', 'trashed' => 'danger', 'restored' => 'info', default => 'warning',
                    }),
                $withModel ? TextColumn::make('auditable_type')->label(__('larawebkit-logs.fields.model'))->formatStateUsing(fn (string $state) => class_basename(Relation::getMorphedModel($state) ?? $state))->searchable() : null,
                $withModel ? TextColumn::make('auditable_label')->label(__('larawebkit-logs.fields.record'))->description(fn (AuditLog $r) => '#'.$r->auditable_id)->searchable()->limit(40) : null,
                TextColumn::make('changes')->label(__('larawebkit-logs.fields.changes'))->state(fn (AuditLog $r) => implode(', ', $r->changedKeys()))->limit(60),
                TextColumn::make('user.name')->label(__('larawebkit-logs.fields.user'))->placeholder(__('larawebkit-logs.system')),
                TextColumn::make('ip_address')->label(__('larawebkit-logs.fields.ip'))->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('reason')->label(__('larawebkit-logs.fields.reason'))->toggleable()->limit(40),
            ]))
            ->filters(array_filter([
                SelectFilter::make('event')->label(__('larawebkit-logs.fields.event'))->options(collect(['created', 'updated', 'deleted', 'trashed', 'restored', 'published', 'gate_override'])->mapWithKeys(fn ($e) => [$e => __("larawebkit-logs.audit.events.$e")])->all()),
                $withModel ? SelectFilter::make('auditable_type')->label(__('larawebkit-logs.fields.model'))->options(fn () => AuditLog::query()->distinct()->pluck('auditable_type')->mapWithKeys(fn ($t) => [$t => class_basename(Relation::getMorphedModel($t) ?? $t)])->all()) : null,
                SelectFilter::make('user_id')->label(__('larawebkit-logs.fields.user'))->relationship('user', 'name')->searchable()->preload(),
                Filter::make('created_at')->schema([
                    DatePicker::make('from')->label(__('larawebkit-logs.fields.from')),
                    DatePicker::make('until')->label(__('larawebkit-logs.fields.until')),
                ])->query(fn (Builder $query, array $data) => $query
                    ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
                    ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))),
            ]))
            ->recordActions([ViewAction::make()]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(3)->schema([
                TextEntry::make('created_at')->label(__('larawebkit-logs.fields.time'))->dateTime(),
                TextEntry::make('event')->label(__('larawebkit-logs.fields.event'))->formatStateUsing(fn (string $state) => __("larawebkit-logs.audit.events.$state")),
                TextEntry::make('user.name')->label(__('larawebkit-logs.fields.user'))->placeholder(__('larawebkit-logs.system')),
                TextEntry::make('auditable_type')->label(__('larawebkit-logs.fields.model')),
                TextEntry::make('auditable_id')->label('ID'),
                TextEntry::make('auditable_label')->label(__('larawebkit-logs.fields.record')),
                TextEntry::make('ip_address')->label(__('larawebkit-logs.fields.ip')),
                TextEntry::make('user_agent')->label(__('larawebkit-logs.fields.user_agent'))->columnSpan(2),
                TextEntry::make('reason')->label(__('larawebkit-logs.fields.reason'))->columnSpanFull()->placeholder('—'),
            ]),
            Section::make(__('larawebkit-logs.fields.changes'))->columns(2)->schema([
                KeyValueEntry::make('old_values')->label(__('larawebkit-logs.fields.old'))->state(fn (AuditLog $r) => self::stringify($r->old_values)),
                KeyValueEntry::make('new_values')->label(__('larawebkit-logs.fields.new'))->state(fn (AuditLog $r) => self::stringify($r->new_values)),
            ]),
        ]);
    }

    /** @return array<string, string> */
    private static function stringify(?array $values): array
    {
        return collect($values ?? [])->map(fn ($v) => is_scalar($v) || $v === null ? (string) $v : json_encode($v, JSON_UNESCAPED_UNICODE))->all();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditLogs::route('/'),
            'view' => ViewAuditLog::route('/{record}'),
        ];
    }
}
