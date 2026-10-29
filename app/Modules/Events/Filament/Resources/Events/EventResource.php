<?php

namespace App\Modules\Events\Filament\Resources\Events;

use App\Modules\Editor\Filament\EditorSchema;
use App\Modules\Events\Filament\Resources\Events\Pages\CreateEvent;
use App\Modules\Events\Filament\Resources\Events\Pages\EditEvent;
use App\Modules\Events\Filament\Resources\Events\Pages\ListEvents;
use App\Modules\Events\Filament\Resources\Events\RelationManagers\RegistrationsRelationManager;
use App\Modules\Events\Mail\RegistrationConfirmation;
use App\Modules\Events\Models\Event;
use App\Modules\Media\Filament\MediaPicker;
use BackedEnum;
use DateTimeZone;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;
use UnitEnum;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('larawebkit.nav.content');
    }

    public static function getModelLabel(): string
    {
        return __('larawebkit-events.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('larawebkit-events.plural');
    }

    public static function canViewAny(): bool
    {
        return (auth()->user()?->can('manage events') ?? false) && setting('features.events', false);
    }

    public static function form(Schema $schema): Schema
    {
        $tz = fn (Get $get) => $get('timezone') ?: 'UTC';
        $locales = (array) config('larawebkit.locales');

        return $schema->components([
            Section::make()->schema([
                TextInput::make('title')->label(__('larawebkit-events.fields.title'))->required()->maxLength(160)->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, Get $get, ?string $state, string $operation) => $operation === 'create' && blank($get('slug')) ? $set('slug', Str::slug((string) $state)) : null)
                    ->columnSpan(2),
                Select::make('status')->label(__('larawebkit-events.fields.status'))->required()->native(false)->default('draft')->selectablePlaceholder(false)
                    ->options(collect(['draft', 'published', 'cancelled'])->mapWithKeys(fn ($s) => [$s => __("larawebkit-events.status.$s")])->all()),
                TextInput::make('slug')->label(__('larawebkit-events.fields.slug'))->alphaDash()->maxLength(120)
                    ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('locale', $get('locale') ?: setting('general.default_locale', 'en'))),
                Select::make('locale')->label(__('larawebkit-events.fields.locale'))->native(false)->options($locales)->default(fn () => setting('general.default_locale', 'en'))
                    ->required()->visible(count($locales) > 1),
                Toggle::make('registration_open')->label(__('larawebkit-events.fields.registration_open'))->default(true)->inline(false),
                Textarea::make('summary')->label(__('larawebkit-events.fields.summary'))->rows(2)->maxLength(300)->columnSpanFull(),
                EditorSchema::richEditor('description')->label(__('larawebkit-events.fields.description'))->columnSpanFull(),
            ])->columns(3),
            Section::make(__('larawebkit-events.when'))->schema([
                Select::make('timezone')->label(__('larawebkit-events.fields.timezone'))->helperText(__('larawebkit-events.help.timezone'))->searchable()->required()->live()
                    ->options(array_combine(DateTimeZone::listIdentifiers(), DateTimeZone::listIdentifiers()))->default(fn () => setting('general.timezone') ?: config('app.timezone', 'UTC')),
                DateTimePicker::make('starts_at')->label(__('larawebkit-events.fields.starts_at'))->required()->seconds(false)->timezone($tz),
                DateTimePicker::make('ends_at')->label(__('larawebkit-events.fields.ends_at'))->helperText(__('larawebkit-events.help.ends_at'))->seconds(false)->timezone($tz)->after('starts_at'),
                TextInput::make('capacity')->label(__('larawebkit-events.fields.capacity'))->helperText(__('larawebkit-events.help.capacity'))->numeric()->minValue(1)->maxValue(1000000),
            ])->columns(4),
            Section::make(__('larawebkit-events.where'))->schema([
                Select::make('location_type')->label(__('larawebkit-events.fields.location_type'))->required()->native(false)->live()->default('online')->selectablePlaceholder(false)
                    ->options(collect(Event::LOCATION_TYPES)->mapWithKeys(fn ($t) => [$t => __("larawebkit-events.location.$t")])->all()),
                TextInput::make('online_url')->label(__('larawebkit-events.fields.online_url'))->helperText(__('larawebkit-events.help.online_url'))->url()->maxLength(1000)
                    ->visible(fn (Get $get) => in_array($get('location_type'), ['online', 'hybrid'], true))->columnSpan(2),
                TextInput::make('venue_name')->label(__('larawebkit-events.fields.venue_name'))->maxLength(160)
                    ->visible(fn (Get $get) => in_array($get('location_type'), ['venue', 'hybrid'], true)),
                Textarea::make('address')->label(__('larawebkit-events.fields.address'))->rows(2)->maxLength(500)
                    ->visible(fn (Get $get) => in_array($get('location_type'), ['venue', 'hybrid'], true))->columnSpan(2),
                MediaPicker::make('featured_media_id')->label(__('larawebkit-events.fields.featured_media'))->columnSpanFull(),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('starts_at', 'desc')
            ->columns([
                TextColumn::make('title')->label(__('larawebkit-events.fields.title'))->searchable()->limit(60),
                TextColumn::make('starts_at')->label(__('larawebkit-events.fields.starts_at'))->sortable()
                    ->formatStateUsing(fn (Event $record) => $record->localStart()->isoFormat('lll').' ('.$record->timezone.')'),
                TextColumn::make('status')->label(__('larawebkit-events.fields.status'))->badge()
                    ->color(fn (string $state) => ['draft' => 'gray', 'published' => 'success', 'cancelled' => 'danger'][$state] ?? 'gray')
                    ->formatStateUsing(fn (string $state) => __("larawebkit-events.status.$state")),
                TextColumn::make('location_type')->label(__('larawebkit-events.fields.location_type'))->formatStateUsing(fn (string $state) => __("larawebkit-events.location.$state")),
                TextColumn::make('registrations_count')->label(__('larawebkit-events.registrations'))->counts(['registrations' => fn ($q) => $q->where('status', 'registered')])
                    ->formatStateUsing(fn (int $state, Event $record) => $record->capacity ? "$state / {$record->capacity}" : (string) $state),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function sendLinkAction(): Action
    {
        $count = fn (Event $record) => $record->registrations()->where('status', 'registered')->count();

        return Action::make('sendLink')->label(__('larawebkit-events.send_link'))->icon('heroicon-o-paper-airplane')
            ->visible(fn (Event $record) => $record->isOnline())
            ->requiresConfirmation()
            ->modalDescription(fn (Event $record) => __('larawebkit-events.send_link_confirm', ['count' => $count($record)]))
            ->action(function (Event $record) {
                if (blank($record->online_url)) {
                    Notification::make()->warning()->title(__('larawebkit-events.send_link_missing'))->send();

                    return;
                }
                $sent = 0;
                $record->registrations()->where('status', 'registered')->chunkById(200, function ($registrations) use (&$sent) {
                    foreach ($registrations as $registration) {
                        Mail::to($registration->email, $registration->name)->queue(new RegistrationConfirmation($registration, link: true));
                        $registration->update(['link_sent_at' => now()]);
                        $sent++;
                    }
                });
                Notification::make()->success()->title(__('larawebkit-events.send_link_done', ['count' => $sent]))->send();
            });
    }

    public static function getRelations(): array
    {
        return [RegistrationsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEvents::route('/'),
            'create' => CreateEvent::route('/create'),
            'edit' => EditEvent::route('/{record}/edit'),
        ];
    }
}
