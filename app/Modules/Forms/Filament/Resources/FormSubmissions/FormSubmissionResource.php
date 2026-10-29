<?php

namespace App\Modules\Forms\Filament\Resources\FormSubmissions;

use App\Modules\Core\Downloads\SignedDownload;
use App\Modules\Forms\Filament\Resources\FormSubmissions\Pages\ListFormSubmissions;
use App\Modules\Forms\Filament\Resources\FormSubmissions\Pages\ViewFormSubmission;
use App\Modules\Forms\Models\Form;
use App\Modules\Forms\Models\FormSubmission;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use UnitEnum;

/** Inbox of all form submissions: status, notes, reply, files, CSV export per form. */
class FormSubmissionResource extends Resource
{
    protected static ?string $model = FormSubmission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    protected static ?int $navigationSort = 51;

    protected static ?string $slug = 'form-submissions';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('larawebkit.nav.marketing');
    }

    public static function getModelLabel(): string
    {
        return __('larawebkit-forms.submission');
    }

    public static function getPluralModelLabel(): string
    {
        return __('larawebkit-forms.submissions');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = FormSubmission::where('status', 'new')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('manage forms') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function statusOptions(): array
    {
        return collect(FormSubmission::STATUSES)->mapWithKeys(fn ($s) => [$s => __("larawebkit-forms.status.$s")])->all();
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                TextEntry::make('form.name')->label(__('larawebkit-forms.singular')),
                TextEntry::make('status')->label(__('larawebkit-forms.csv.status'))->badge()->formatStateUsing(fn ($state) => __("larawebkit-forms.status.$state")),
                TextEntry::make('created_at')->label(__('larawebkit-forms.csv.date'))->dateTime(),
                TextEntry::make('email')->label(__('larawebkit-forms.types.email'))->copyable()->placeholder('—'),
            ])->columns(4),
            Section::make(__('larawebkit-forms.answers'))->schema([
                KeyValueEntry::make('answers')->hiddenLabel()->state(fn (FormSubmission $record) => $record->readable())
                    ->keyLabel(__('larawebkit-forms.fields.label'))->valueLabel(__('larawebkit-forms.answer')),
                TextEntry::make('files')->label(__('larawebkit-forms.files'))->visible(fn (FormSubmission $record) => ! empty($record->files))
                    ->state(fn (FormSubmission $record) => new HtmlString(collect((array) $record->files)->map(fn ($f) => '<a class="lwk-link" href="'.e(SignedDownload::url($f['path'], $f['name'], 1)).'">'.e($f['name']).'</a> ('.round(($f['size'] ?? 0) / 1024).' KB)')->implode('<br>'))),
                TextEntry::make('consent')->label(__('larawebkit-forms.types.consent'))->visible(fn (FormSubmission $record) => $record->consent_version !== null)
                    ->state(fn (FormSubmission $record) => __('larawebkit-forms.consent_given', ['version' => $record->consent_version]).' — '.$record->consent_text),
                TextEntry::make('source_url')->label(__('larawebkit-forms.source'))->placeholder('—'),
            ]),
            Section::make(__('larawebkit-forms.fields.notes'))->schema([TextEntry::make('notes')->hiddenLabel()->placeholder('—')->prose()]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('status')->label(__('larawebkit-forms.csv.status'))->badge()
                    ->color(fn (string $state) => ['new' => 'warning', 'read' => 'gray', 'replied' => 'success', 'archived' => 'gray', 'spam' => 'danger'][$state] ?? 'gray')
                    ->formatStateUsing(fn ($state) => __("larawebkit-forms.status.$state")),
                TextColumn::make('form.name')->label(__('larawebkit-forms.singular'))->toggleable(),
                TextColumn::make('email')->label(__('larawebkit-forms.types.email'))->searchable()->placeholder('—'),
                TextColumn::make('summary')->label(__('larawebkit-forms.answers'))->state(fn (FormSubmission $r) => collect($r->readable())->filter()->take(3)->implode(' · '))->limit(80)->wrap(),
                TextColumn::make('created_at')->label(__('larawebkit-forms.csv.date'))->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('larawebkit-forms.csv.status'))->options(self::statusOptions()),
                SelectFilter::make('form_id')->label(__('larawebkit-forms.singular'))->options(fn () => Form::orderBy('name')->pluck('name', 'id')),
            ])
            ->recordActions([
                ViewAction::make()->after(fn (FormSubmission $record) => $record->status === 'new' ? $record->update(['status' => 'read']) : null),
                Action::make('reply')->label(__('larawebkit-forms.reply'))->icon('heroicon-o-envelope')->visible(fn (FormSubmission $r) => filled($r->email))
                    ->url(fn (FormSubmission $r) => 'mailto:'.$r->email.'?subject='.rawurlencode('Re: '.$r->form?->name))->openUrlInNewTab()
                    ->after(fn (FormSubmission $r) => $r->update(['status' => 'replied'])),
                self::statusAction(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkAction::make('mark')->label(__('larawebkit-forms.change_status'))->icon('heroicon-o-tag')
                    ->schema([Select::make('status')->options(self::statusOptions())->required()])
                    ->action(fn (Collection $records, array $data) => $records->each->update(['status' => $data['status']]))->deselectRecordsAfterCompletion(),
                DeleteBulkAction::make(),
            ]);
    }

    public static function statusAction(): Action
    {
        return Action::make('status')->label(__('larawebkit-forms.status_and_notes'))->icon('heroicon-o-pencil-square')->color('gray')
            ->fillForm(fn (FormSubmission $record) => ['status' => $record->status, 'notes' => $record->notes])
            ->schema([
                Select::make('status')->label(__('larawebkit-forms.csv.status'))->options(self::statusOptions())->required()->native(false),
                Textarea::make('notes')->label(__('larawebkit-forms.fields.notes'))->rows(4)->maxLength(5000),
            ])
            ->action(fn (FormSubmission $record, array $data) => $record->update($data));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFormSubmissions::route('/'),
            'view' => ViewFormSubmission::route('/{record}'),
        ];
    }
}
