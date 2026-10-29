<?php

namespace App\Modules\Newsletter\Filament\Resources\Campaigns;

use App\Modules\Editor\Filament\EditorSchema;
use App\Modules\Newsletter\CampaignSender;
use App\Modules\Newsletter\Filament\Resources\Campaigns\Pages\CreateCampaign;
use App\Modules\Newsletter\Filament\Resources\Campaigns\Pages\EditCampaign;
use App\Modules\Newsletter\Filament\Resources\Campaigns\Pages\ListCampaigns;
use App\Modules\Newsletter\Filament\Resources\Campaigns\RelationManagers\SendsRelationManager;
use App\Modules\Newsletter\Mail\CampaignMail;
use App\Modules\Newsletter\Models\Campaign;
use App\Modules\Newsletter\Models\Subscriber;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use UnitEnum;

/** Compose, preview, test-send, send or schedule a newsletter; send log and stats per campaign. */
class CampaignResource extends Resource
{
    protected static ?string $model = Campaign::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperAirplane;

    protected static ?int $navigationSort = 60;

    protected static ?string $recordTitleAttribute = 'subject';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('larawebkit.nav.marketing');
    }

    public static function getModelLabel(): string
    {
        return __('larawebkit-newsletter.campaign');
    }

    public static function getPluralModelLabel(): string
    {
        return __('larawebkit-newsletter.campaigns');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('manage newsletter') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                TextInput::make('subject')->label(__('larawebkit-newsletter.fields.subject'))->required()->maxLength(150),
                TextInput::make('preheader')->label(__('larawebkit-newsletter.fields.preheader'))->helperText(__('larawebkit-newsletter.help.preheader'))->maxLength(150),
                Select::make('locale')->label(__('larawebkit-newsletter.fields.audience'))->native(false)
                    ->options(['' => __('larawebkit-newsletter.all_languages')] + (array) config('larawebkit.locales'))
                    ->helperText(__('larawebkit-newsletter.help.audience')),
                EditorSchema::richEditor('body')->label(__('larawebkit-newsletter.fields.body'))->required()->columnSpanFull(),
            ])->columns(3)->disabled(fn (?Campaign $record) => $record && ! $record->isEditable()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('subject')->label(__('larawebkit-newsletter.fields.subject'))->searchable()->limit(60),
                TextColumn::make('status')->label(__('larawebkit-newsletter.fields.status'))->badge()
                    ->color(fn (string $state) => ['draft' => 'gray', 'scheduled' => 'info', 'sending' => 'warning', 'sent' => 'success', 'cancelled' => 'danger'][$state] ?? 'gray')
                    ->formatStateUsing(fn (string $state) => __("larawebkit-newsletter.status.$state")),
                TextColumn::make('progress')->label(__('larawebkit-newsletter.stats.sent'))
                    ->state(fn (Campaign $c) => $c->recipients_count ? $c->sent_count.' / '.$c->recipients_count : '—'),
                TextColumn::make('failed_count')->label(__('larawebkit-newsletter.stats.failed')),
                TextColumn::make('unsubscribes')->label(__('larawebkit-newsletter.stats.unsubscribed'))->state(fn (Campaign $c) => $c->status === 'draft' ? '—' : $c->unsubscribedCount()),
                TextColumn::make('scheduled_at')->label(__('larawebkit-newsletter.fields.scheduled_at'))->dateTime()->placeholder('—')->toggleable(),
                TextColumn::make('finished_at')->label(__('larawebkit-newsletter.fields.finished_at'))->since()->placeholder('—'),
            ])
            ->recordActions([EditAction::make()]);
    }

    /** @return list<Action> actions on the edit page */
    public static function campaignActions(): array
    {
        $recipients = fn (Campaign $c) => Subscriber::active()->when($c->locale, fn ($q, $l) => $q->where('locale', $l))->count();

        return [
            Action::make('preview')->label(__('larawebkit-newsletter.preview'))->icon('heroicon-o-eye')->color('gray')
                ->modalSubmitAction(false)->modalWidth('3xl')
                ->modalContent(fn (Campaign $record) => new HtmlString('<iframe title="'.e(__('larawebkit-newsletter.preview')).'" style="width:100%;height:70vh;border:0;border-radius:.5rem;background:#fff" srcdoc="'.e((new CampaignMail($record, new Subscriber(['email' => 'preview@example.com'])))->render()).'"></iframe>')),
            Action::make('test')->label(__('larawebkit-newsletter.test_send'))->icon('heroicon-o-beaker')->color('gray')
                ->schema([TextInput::make('email')->label(__('larawebkit-newsletter.fields.test_email'))->email()->required()->default(fn () => auth()->user()?->email)])
                ->action(function (Campaign $record, array $data) {
                    app(CampaignSender::class)->sendTest($record, $data['email']);
                    Notification::make()->success()->title(__('larawebkit-newsletter.test_sent', ['email' => $data['email']]))->send();
                }),
            Action::make('schedule')->label(__('larawebkit-newsletter.schedule'))->icon('heroicon-o-clock')->color('gray')
                ->visible(fn (Campaign $record) => $record->isEditable())
                ->schema([DateTimePicker::make('scheduled_at')->label(__('larawebkit-newsletter.fields.scheduled_at'))->required()->seconds(false)->minDate(now())])
                ->action(function (Campaign $record, array $data) {
                    $record->update(['status' => 'scheduled', 'scheduled_at' => $data['scheduled_at']]);
                    Notification::make()->success()->title(__('larawebkit-newsletter.scheduled', ['date' => $record->scheduled_at->isoFormat('LLL')]))->send();
                }),
            Action::make('send')->label(__('larawebkit-newsletter.send_now'))->icon('heroicon-o-paper-airplane')
                ->visible(fn (Campaign $record) => $record->isEditable())
                ->requiresConfirmation()
                ->modalDescription(fn (Campaign $record) => __('larawebkit-newsletter.confirm_send', ['count' => $recipients($record), 'rate' => config('larawebkit-newsletter.batch_size')]))
                ->action(function (Campaign $record) {
                    $count = app(CampaignSender::class)->start($record);
                    Notification::make()->success()->title(__('larawebkit-newsletter.sending', ['count' => $count]))->send();
                }),
            Action::make('cancel')->label(__('larawebkit-newsletter.cancel'))->icon('heroicon-o-x-circle')->color('danger')
                ->visible(fn (Campaign $record) => in_array($record->status, ['scheduled', 'sending'], true))->requiresConfirmation()
                ->action(fn (Campaign $record) => $record->update(['status' => 'cancelled'])),
        ];
    }

    public static function getRelations(): array
    {
        return [SendsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCampaigns::route('/'),
            'create' => CreateCampaign::route('/create'),
            'edit' => EditCampaign::route('/{record}/edit'),
        ];
    }
}
