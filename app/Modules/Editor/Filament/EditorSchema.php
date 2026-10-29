<?php

namespace App\Modules\Editor\Filament;

use App\Models\User;
use App\Modules\Blocks\Filament\BlocksField;
use App\Modules\Content\Models\Category;
use App\Modules\Content\Models\Page;
use App\Modules\Content\Models\Post;
use App\Modules\Content\Models\Tag;
use App\Modules\Core\ModuleRegistry;
use App\Modules\Media\ImagePipeline;
use App\Modules\Media\Models\Media;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * The content editor form shared by pages and posts: rich text with the
 * media modal, keyword chips, meta + snippet preview, citability (GEO)
 * fields, publishing sidebar and the live SEO / human / GEO panel.
 */
class EditorSchema
{
    /** @return array<int, mixed> */
    public static function components(string $type): array
    {
        return [
            Grid::make(['default' => 1, 'lg' => 3])->schema([
                Group::make(self::main($type))->columnSpan(['lg' => 2]),
                Group::make(self::sidebar($type))->columnSpan(['lg' => 1]),
            ])->columnSpanFull(),
        ];
    }

    public static function richEditor(string $name = 'content'): RichEditor
    {
        return RichEditor::make($name)
            ->label(__('larawebkit-editor.fields.content'))
            ->toolbarButtons([
                ['h2', 'h3'],
                ['bold', 'italic', 'underline', 'link'],
                ['bulletList', 'orderedList', 'blockquote'],
                ['mediaLibrary', 'attachFiles'],
                ['clearFormatting', 'undo', 'redo'],
            ])
            ->plugins([MediaLibraryPlugin::make()])
            ->fileAttachmentsAcceptedFileTypes((array) config('larawebkit-media.accepted_mime_types'))
            ->fileAttachmentsMaxSize((int) config('larawebkit-media.max_upload_kb'))
            // Pasted / dropped images also go through the WebP pipeline into the media library.
            ->saveUploadedFileAttachmentUsing(function (TemporaryUploadedFile $file) {
                try {
                    return (string) app(ImagePipeline::class)->storeUpload($file, ['uploaded_by' => auth()->id()])->id;
                } catch (InvalidArgumentException $e) {
                    Notification::make()->danger()->title($file->getClientOriginalName())->body($e->getMessage())->persistent()->send();

                    return null;
                }
            })
            ->getFileAttachmentUrlUsing(fn ($id) => is_numeric($id) ? Media::find($id)?->url(1280) : null)
            ->extraInputAttributes(['style' => 'min-height: 28rem', 'data-lwk-editor' => '1'])
            ->stickyToolbar();
    }

    /** @return array<int, mixed> */
    private static function main(string $type): array
    {
        $prefix = rtrim(url('/'), '/').($type === 'post' ? post_path('', (string) setting('general.default_locale', 'en')) : '/');

        return [
            Section::make()->schema([
                TextInput::make('title')->label(__('larawebkit-editor.fields.title'))->required()->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state, ?string $old, string $operation) {
                        if ($operation === 'create' && (blank($get('slug')) || $get('slug') === Str::slug((string) $old))) {
                            $set('slug', Str::slug((string) $state));
                        }
                    })
                    ->extraInputAttributes(['data-lwk-field' => 'title', 'style' => 'font-size:1.25rem;font-weight:600']),
                TextInput::make('slug')->label(__('larawebkit-editor.fields.slug'))->prefix($prefix)->alphaDash()->maxLength(190)
                    ->helperText(__('larawebkit-editor.help.slug'))
                    ->extraInputAttributes(['data-lwk-field' => 'slug']),
                ...($type === 'post' ? [Textarea::make('excerpt')->label(__('larawebkit-editor.fields.excerpt'))->rows(2)->maxLength(500)] : []),
                ...($type === 'page' && ModuleRegistry::enabled('blocks') ? [BlocksField::preset()] : []),
                self::richEditor()->helperText($type === 'page' && ModuleRegistry::enabled('blocks') ? __('larawebkit-blocks.field.content_help') : null),
            ]),
            ...($type === 'page' && ModuleRegistry::enabled('blocks')
                ? [Section::make(__('larawebkit-blocks.field.section'))->description(__('larawebkit-blocks.field.section_help'))->collapsible()->schema([BlocksField::make()])]
                : []),
            Section::make(__('larawebkit-editor.sections.citability'))
                ->description(__('larawebkit-editor.help.citability'))
                ->collapsible()
                ->schema([
                    Textarea::make('summary')->label(__('larawebkit-editor.fields.summary'))->rows(3)->maxLength(1000)
                        ->helperText(__('larawebkit-editor.help.summary'))->extraInputAttributes(['data-lwk-field' => 'summary']),
                    Repeater::make('faq')->label(__('larawebkit-editor.fields.faq'))
                        ->schema([
                            TextInput::make('question')->label(__('larawebkit-editor.fields.question'))->required()->maxLength(300),
                            Textarea::make('answer')->label(__('larawebkit-editor.fields.answer'))->required()->rows(3)->maxLength(3000),
                        ])
                        ->defaultItems(0)->collapsible()->reorderable()
                        ->itemLabel(fn (array $state) => $state['question'] ?? null)
                        ->addActionLabel(__('larawebkit-editor.fields.faq_add')),
                    Textarea::make('sources')->label(__('larawebkit-editor.fields.sources'))->rows(3)
                        ->helperText(__('larawebkit-editor.help.sources'))->extraInputAttributes(['data-lwk-field' => 'sources']),
                ]),
            Section::make(__('larawebkit-editor.sections.seo'))->collapsible()->schema([
                TagsInput::make('keywords')->label(__('larawebkit-editor.fields.keywords'))
                    ->helperText(__('larawebkit-editor.help.keywords'))->splitKeys(['Enter', ','])->reorderable(),
                TextInput::make('meta_title')->label(__('larawebkit-editor.fields.meta_title'))->maxLength(70)
                    ->extraInputAttributes(['data-lwk-field' => 'meta_title']),
                Textarea::make('meta_description')->label(__('larawebkit-editor.fields.meta_description'))->rows(2)->maxLength(320)
                    ->extraInputAttributes(['data-lwk-field' => 'meta_description']),
                ViewField::make('snippet')->view('editor.snippet')->dehydrated(false)
                    ->viewData(['prefix' => $prefix]),
                TextInput::make('canonical_url')->label(__('larawebkit-editor.fields.canonical_url'))->url()->maxLength(500),
                Toggle::make('noindex')->label(__('larawebkit-editor.fields.noindex')),
            ]),
        ];
    }

    /** @return array<int, mixed> */
    private static function sidebar(string $type): array
    {
        $canPublish = fn () => auth()->user()?->can('publish content') ?? false;
        $locales = (array) config('larawebkit.locales');

        return [
            Section::make(__('larawebkit-editor.sections.publish'))->schema([
                Select::make('status')->label(__('larawebkit-editor.fields.status'))->native(false)->required()
                    ->options(fn () => $canPublish()
                        ? ['draft' => __('larawebkit-content.status.draft'), 'pending' => __('larawebkit-content.status.pending'), 'published' => __('larawebkit-content.status.published')]
                        : ['draft' => __('larawebkit-content.status.draft'), 'pending' => __('larawebkit-content.status.pending')])
                    ->default('draft')->live(),
                DateTimePicker::make('published_at')->label(__('larawebkit-editor.fields.published_at'))
                    ->helperText(__('larawebkit-editor.help.published_at'))->seconds(false)
                    ->visible(fn (Get $get) => $get('status') === 'published'),
                Textarea::make('gate_override_reason')->label(__('larawebkit-editor.fields.gate_override_reason'))->rows(2)->maxLength(500)
                    ->helperText(__('larawebkit-editor.help.gate_override_reason'))
                    ->visible(fn (Get $get) => $get('status') === 'published' && (auth()->user()?->can('override publish gate') ?? false)),
                Select::make('author_id')->label(__('larawebkit-editor.fields.author'))
                    ->options(fn () => User::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                    ->default(fn () => auth()->id())->searchable()
                    ->disabled(fn () => ! (auth()->user()?->can($type === 'post' ? 'manage posts' : 'manage pages') ?? false))
                    ->dehydrated(),
                Select::make('locale')->label(__('larawebkit-editor.fields.locale'))->options($locales)
                    ->default(fn () => setting('general.default_locale', 'en'))->required()->native(false)
                    ->visible(count((array) setting('general.active_locales', ['en'])) > 1 || count($locales) > 1),
                Select::make('translation_of')->label(__('larawebkit-editor.fields.translation_of'))
                    ->helperText(__('larawebkit-editor.help.translation_of'))
                    ->options(fn (Get $get, $record) => ($type === 'post' ? Post::query() : Page::query())
                        ->where('locale', '!=', $get('locale') ?? 'en')
                        ->when($record, fn ($q) => $q->whereKeyNot($record->getKey()))
                        ->orderBy('title')->limit(200)->get()->mapWithKeys(fn ($m) => [$m->id => '['.$m->locale.'] '.$m->title]))
                    ->searchable()->dehydrated(false)
                    ->afterStateHydrated(function (Select $component, $record) use ($type) {
                        if ($record?->translation_group) {
                            $other = ($type === 'post' ? Post::query() : Page::query())->where('translation_group', $record->translation_group)->whereKeyNot($record->getKey())->value('id');
                            $component->state($other);
                        }
                    }),
                ...($type === 'post'
                    ? [
                        Select::make('categories')->label(__('larawebkit-content.categories.plural'))->relationship('categories', 'name')->multiple()->preload()
                            ->createOptionForm([TextInput::make('name')->required()])
                            ->createOptionUsing(fn (array $data) => Category::create($data)->getKey()),
                        Select::make('tags')->label(__('larawebkit-content.tags.plural'))->relationship('tags', 'name')->multiple()->preload()
                            ->createOptionForm([TextInput::make('name')->required()])
                            ->createOptionUsing(fn (array $data) => Tag::create($data)->getKey()),
                        Toggle::make('members_only')->label(__('larawebkit-editor.fields.members_only'))->helperText(__('larawebkit-editor.help.members_only')),
                    ]
                    : [
                        Select::make('parent_id')->label(__('larawebkit-editor.fields.parent'))
                            ->options(fn ($record) => Page::query()->when($record, fn ($q) => $q->whereKeyNot($record->getKey()))->orderBy('path')->pluck('path', 'id'))
                            ->searchable(),
                        Select::make('template')->label(__('larawebkit-editor.fields.template'))->native(false)->required()->default('default')
                            ->options(collect(Page::TEMPLATES)->mapWithKeys(fn ($t) => [$t => __("larawebkit-content.templates.$t")])->all()),
                        TextInput::make('sort_order')->label(__('larawebkit-editor.fields.sort_order'))->numeric()->default(0),
                    ]),
            ]),
            Section::make(__('larawebkit-editor.sections.featured'))->schema([
                Hidden::make('featured_media_id'),
                ViewField::make('featured_picker')->view('editor.featured-image')->dehydrated(false),
            ]),
            ViewField::make('editor_panel')->view('editor.panel')->dehydrated(false)
                ->viewData(['type' => $type]),
        ];
    }
}
