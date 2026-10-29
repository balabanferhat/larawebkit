<?php

namespace App\Modules\Forms\Filament\Resources\Forms;

use App\Modules\Forms\FieldTypes;
use App\Modules\Forms\Filament\RelationManagers\SubmissionsRelationManager;
use App\Modules\Forms\Filament\Resources\Forms\Pages\CreateForm;
use App\Modules\Forms\Filament\Resources\Forms\Pages\EditForm;
use App\Modules\Forms\Filament\Resources\Forms\Pages\ListForms;
use App\Modules\Forms\Models\Form;
use App\Modules\Logs\Filament\RelationManagers\AuditLogsRelationManager;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class FormResource extends Resource
{
    protected static ?string $model = Form::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?int $navigationSort = 50;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('larawebkit.nav.marketing');
    }

    public static function getModelLabel(): string
    {
        return __('larawebkit-forms.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('larawebkit-forms.plural');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('manage forms') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                TextInput::make('name')->label(__('larawebkit-forms.fields.name'))->required()->maxLength(120)->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, Get $get, ?string $state, string $operation) => $operation === 'create' && blank($get('slug')) ? $set('slug', Str::slug((string) $state)) : null),
                TextInput::make('slug')->label(__('larawebkit-forms.fields.slug'))->required()->alphaDash()->maxLength(80)->unique(ignoreRecord: true)
                    ->helperText(__('larawebkit-forms.help.slug')),
                Toggle::make('is_active')->label(__('larawebkit-forms.fields.is_active'))->default(true),
            ])->columns(3),
            Section::make(__('larawebkit-forms.sections.fields'))->schema([
                Repeater::make('fields')->hiddenLabel()->required()->minItems(1)->reorderable()->collapsible()->cloneable()
                    ->itemLabel(fn (array $state) => trim(($state['label'] ?? '').' · '.__('larawebkit-forms.types.'.($state['type'] ?? 'text')), ' ·'))
                    ->addActionLabel(__('larawebkit-forms.add_field'))
                    ->schema([
                        Select::make('type')->label(__('larawebkit-forms.fields.type'))->required()->native(false)->live()->default('text')
                            ->options(collect(FieldTypes::TYPES)->mapWithKeys(fn ($t) => [$t => __("larawebkit-forms.types.$t")])->all()),
                        TextInput::make('label')->label(__('larawebkit-forms.fields.label'))->required()->maxLength(500)->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set, Get $get, ?string $state) => blank($get('key')) ? $set('key', Str::limit(Str::slug((string) $state, '_'), 40, '')) : null),
                        TextInput::make('key')->label(__('larawebkit-forms.fields.key'))->required()->maxLength(40)->regex('/^[a-z][a-z0-9_]*$/')
                            ->helperText(__('larawebkit-forms.help.key')),
                        Select::make('width')->label(__('larawebkit-forms.fields.width'))->native(false)->default('full')->selectablePlaceholder(false)
                            ->options(['full' => __('larawebkit-forms.width.full'), 'half' => __('larawebkit-forms.width.half')]),
                        Toggle::make('required')->label(__('larawebkit-forms.fields.required'))->inline(false)->hidden(fn (Get $get) => $get('type') === 'consent'),
                        TextInput::make('placeholder')->label(__('larawebkit-forms.fields.placeholder'))->maxLength(120)
                            ->visible(fn (Get $get) => in_array($get('type'), ['text', 'email', 'phone', 'textarea', 'select'], true)),
                        TextInput::make('help')->label(__('larawebkit-forms.fields.help'))->maxLength(300),
                        Textarea::make('options')->label(__('larawebkit-forms.fields.options'))->helperText(__('larawebkit-forms.help.options'))->rows(4)
                            ->visible(fn (Get $get) => in_array($get('type'), ['select', 'radio'], true))->required(fn (Get $get) => in_array($get('type'), ['select', 'radio'], true)),
                        TextInput::make('max')->label(__('larawebkit-forms.fields.max'))->numeric()->minValue(1)->maxValue(10000)
                            ->visible(fn (Get $get) => in_array($get('type'), ['text', 'textarea'], true)),
                        CheckboxList::make('accept')->label(__('larawebkit-forms.fields.accept'))->options(array_combine(FieldTypes::FILE_TYPES, FieldTypes::FILE_TYPES))->columns(5)->default(['pdf'])
                            ->visible(fn (Get $get) => $get('type') === 'file')->columnSpanFull(),
                        TextInput::make('max_kb')->label(__('larawebkit-forms.fields.max_kb'))->numeric()->minValue(10)->maxValue((int) config('larawebkit-forms.max_file_kb', 10240))->default(5120)
                            ->visible(fn (Get $get) => $get('type') === 'file'),
                        TextInput::make('consent_version')->label(__('larawebkit-forms.fields.consent_version'))->numeric()->minValue(1)->default(1)
                            ->helperText(__('larawebkit-forms.help.consent_version'))->visible(fn (Get $get) => $get('type') === 'consent'),
                        TextInput::make('link_url')->label(__('larawebkit-forms.fields.link_url'))->maxLength(500)->placeholder('/privacy-policy')
                            ->visible(fn (Get $get) => $get('type') === 'consent'),
                    ])->columns(3),
            ]),
            Section::make(__('larawebkit-forms.sections.after'))->schema([
                TextInput::make('submit_label')->label(__('larawebkit-forms.fields.submit_label'))->maxLength(60)->placeholder(__('larawebkit-forms.submit')),
                TextInput::make('notify_emails')->label(__('larawebkit-forms.fields.notify_emails'))->maxLength(500)->helperText(__('larawebkit-forms.help.notify_emails')),
                Textarea::make('success_message')->label(__('larawebkit-forms.fields.success_message'))->rows(2)->placeholder(__('larawebkit-forms.sent'))->columnSpanFull(),
                Toggle::make('send_confirmation')->label(__('larawebkit-forms.fields.send_confirmation'))->helperText(__('larawebkit-forms.help.send_confirmation'))->live(),
                TextInput::make('confirmation_subject')->label(__('larawebkit-forms.fields.confirmation_subject'))->maxLength(150)->visible(fn (Get $get) => $get('send_confirmation') || filled($get('download_path'))),
                Textarea::make('confirmation_body')->label(__('larawebkit-forms.fields.confirmation_body'))->rows(4)->visible(fn (Get $get) => $get('send_confirmation') || filled($get('download_path')))->columnSpanFull(),
            ])->columns(2),
            Section::make(__('larawebkit-forms.sections.download'))->description(__('larawebkit-forms.help.download'))->collapsible()->schema([
                FileUpload::make('download_path')->label(__('larawebkit-forms.fields.download'))->disk((string) config('larawebkit.download_disk', 'local'))
                    ->directory((string) config('larawebkit-forms.downloads_directory', 'downloads'))->visibility('private')
                    ->acceptedFileTypes(['application/pdf', 'application/zip', 'application/epub+zip', 'image/png', 'image/jpeg', 'image/webp', 'text/plain'])
                    ->maxSize(51200)->storeFileNamesIn('download_name')->live(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('larawebkit-forms.fields.name'))->searchable()->sortable(),
                TextColumn::make('slug')->label(__('larawebkit-forms.fields.slug'))->badge()->color('gray'),
                TextColumn::make('submissions_count')->label(__('larawebkit-forms.submissions'))->counts('submissions')->sortable(),
                IconColumn::make('is_active')->label(__('larawebkit-forms.fields.is_active'))->boolean(),
                TextColumn::make('updated_at')->label(__('larawebkit-logs.fields.time'))->since()->sortable(),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getRelations(): array
    {
        return [SubmissionsRelationManager::class, AuditLogsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListForms::route('/'),
            'create' => CreateForm::route('/create'),
            'edit' => EditForm::route('/{record}/edit'),
        ];
    }
}
