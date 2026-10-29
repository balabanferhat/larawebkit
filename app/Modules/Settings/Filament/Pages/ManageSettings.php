<?php

namespace App\Modules\Settings\Filament\Pages;

use App\Modules\Geo\AiCrawlerPolicy;
use App\Modules\Media\Filament\MediaPicker;
use App\Modules\Settings\SettingsRepository;
use App\Modules\Themes\Design\DesignTokens;
use App\Modules\Themes\Design\Fonts;
use App\Modules\Themes\ThemeManager;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\ColorPicker;
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
 * Definitions with a 'page' key belong to another page built the same way
 * (see ManageAppearance), which sets $settingsPage.
 *
 * @property-read Schema $form
 */
class ManageSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = 90;

    /** Which definitions this page edits: those without a 'page' key (null) or with this one. */
    protected static ?string $settingsPage = null;

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
        $repository = app(SettingsRepository::class);
        foreach (array_keys($this->definitions()) as $key) {
            data_set($values, self::field($key), $repository->get($key));
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
        foreach ($this->definitions() as $key => $definition) {
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

        foreach (array_keys($this->definitions()) as $key) {
            $field = self::field($key);
            if (array_key_exists($field, $state)) {
                $repository->set($key, $state[$field]);
            }
        }
        $this->saved();

        Notification::make()->success()->title(__('larawebkit-settings.saved'))->send();
    }

    /** @return array<string, array<string, mixed>> */
    protected function definitions(): array
    {
        return array_filter(
            app(SettingsRepository::class)->definitions(),
            fn (array $definition) => ($definition['page'] ?? null) === static::$settingsPage,
        );
    }

    /** Hook for subclasses (e.g. regenerate derived files). */
    protected function saved(): void {}

    /** @param  array<string, mixed>  $definition */
    protected function component(string $key, array $definition): mixed
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
            'color' => ColorPicker::make($name)->hex(),
            'media' => MediaPicker::make($name),
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
    protected function options(string|array $source): array
    {
        if (is_array($source)) {
            return collect($source)->mapWithKeys(function ($label, $value) {
                $key = 'larawebkit-settings.options.'.Str::snake(str_replace('-', '_', (string) $value));

                return [$value => __($key) !== $key ? __($key) : $label];
            })->all();
        }

        return match ($source) {
            'locales' => (array) config('larawebkit.locales'),
            'themes' => app(ThemeManager::class)->options(),
            'font_pairs' => ['theme' => __('larawebkit-themes.theme_default')] + Fonts::options(),
            'radii' => ['theme' => __('larawebkit-themes.theme_default')] + collect(array_keys(DesignTokens::RADII))->mapWithKeys(fn ($r) => [$r => __("larawebkit-themes.radius.$r")])->all(),
            default => [],
        };
    }
}
