<?php

namespace App\Modules\Settings\Filament\Pages;

use App\Modules\Geo\AiCrawlerPolicy;
use App\Modules\Media\Models\Media;
use App\Modules\Settings\SettingsRepository;
use App\Modules\Themes\ThemeManager;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;
use UnitEnum;

/**
 * One form for every typed setting, grouped in tabs. Built from
 * config/larawebkit-settings.php so a new setting needs no UI code.
 *
 * @property-read Schema $form
 */
class ManageSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = 90;

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('larawebkit.nav.system');
    }

    public static function getNavigationLabel(): string
    {
        return __('larawebkit-settings.title');
    }

    public function getTitle(): string
    {
        return __('larawebkit-settings.title');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('manage settings') ?? false;
    }

    public function mount(): void
    {
        $values = [];
        foreach (app(SettingsRepository::class)->all() as $key => $value) {
            data_set($values, self::field($key), $value);
        }
        $this->form->fill($values);
    }

    public static function field(string $key): string
    {
        return str_replace('.', '__', $key);
    }

    public function form(Schema $schema): Schema
    {
        $groups = [];
        foreach (app(SettingsRepository::class)->definitions() as $key => $definition) {
            $groups[$definition['group']][] = $this->component($key, $definition);
        }

        $tabs = [];
        foreach ($groups as $group => $components) {
            $tabs[] = Tab::make(__("larawebkit-settings.groups.$group"))->schema($components);
        }

        return $schema
            ->components([Tabs::make('settings')->tabs($tabs)->persistTabInQueryString()])
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label(__('larawebkit.actions.save'))->submit('save'),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $repository = app(SettingsRepository::class);

        foreach (array_keys($repository->definitions()) as $key) {
            $field = self::field($key);
            if (array_key_exists($field, $state)) {
                $repository->set($key, $state[$field]);
            }
        }

        Notification::make()->success()->title(__('larawebkit-settings.saved'))->send();
    }

    /** @param  array<string, mixed>  $definition */
    private function component(string $key, array $definition): mixed
    {
        $name = self::field($key);
        $label = __("larawebkit-settings.keys.$key");
        $helpKey = "larawebkit-settings.help.$key";
        $help = __($helpKey) !== $helpKey ? __($helpKey) : null;
        $rules = $definition['rules'] ?? [];

        $component = match ($definition['type']) {
            'bool' => Toggle::make($name),
            'text' => Textarea::make($name)->rows(4),
            'int' => TextInput::make($name)->numeric(),
            'email' => TextInput::make($name)->email(),
            'url' => TextInput::make($name)->url(),
            'select' => Select::make($name)->options($this->options($definition['options'] ?? []))->native(false),
            'multiselect' => CheckboxList::make($name)->options($this->options($definition['options'] ?? []))->columns(3),
            'media' => Select::make($name)
                ->searchable()
                ->getSearchResultsUsing(fn (string $search) => Media::query()->where('file_name', 'like', "%$search%")->orWhere('title', 'like', "%$search%")->limit(30)->pluck('file_name', 'id')->all())
                ->getOptionLabelUsing(fn ($value) => Media::find($value)?->file_name)
                ->options(fn () => Media::query()->latest()->limit(30)->pluck('file_name', 'id')->all()),
            'map' => KeyValue::make($name)->keyLabel(__('larawebkit-settings.map.key'))->valueLabel(__('larawebkit-settings.map.value')),
            'bots' => CheckboxList::make($name)
                ->options(collect(AiCrawlerPolicy::bots())->mapWithKeys(fn ($bot, $name) => [$name => $name.' ('.__('larawebkit-geo.purpose.'.$bot['purpose']).')'])->all())
                ->columns(2)
                ->helperText(__('larawebkit-settings.help.geo.ai_bots')),
            default => TextInput::make($name),
        };

        if ($definition['type'] === 'bots') {
            // Stored as list of allowed bot names (only used when preset = custom).
            return $component->label($label)->visible(fn ($get) => $get(self::field('geo.ai_preset')) === 'custom');
        }

        return $component->label($label)->helperText($help)->rules($rules)->required(in_array('required', $rules, true));
    }

    /** @return array<string, string> */
    private function options(string|array $source): array
    {
        if (is_array($source)) {
            return collect($source)->mapWithKeys(function ($label, $value) {
                $key = 'larawebkit-settings.options.'.Str::snake(str_replace('-', '_', (string) $value));

                return [$value => __($key) !== $key ? __($key) : $label];
            })->all();
        }

        return match ($source) {
            'locales' => (array) config('larawebkit.locales'),
            'themes' => ThemeManager::options(),
            default => [],
        };
    }
}
