<?php

namespace App\Modules\Themes\Filament\Pages;

use App\Modules\Themes\Theme;
use App\Modules\Themes\ThemeAssets;
use App\Modules\Themes\ThemeInstaller;
use App\Modules\Themes\ThemeManager;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use InvalidArgumentException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use UnitEnum;

/**
 * Appearance → Themes: every discovered theme as a card (screenshot, version,
 * author, parent) with an Activate button, plus an optional .zip upload for
 * super-admins (config larawebkit-themes.allow_upload, off by default).
 */
class ManageThemes extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaintBrush;

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'themes';

    protected string $view = 'admin.themes';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('larawebkit.nav.appearance');
    }

    public static function getNavigationLabel(): string
    {
        return __('larawebkit-themes.manage.title');
    }

    public function getTitle(): string
    {
        return __('larawebkit-themes.manage.title');
    }

    public function getSubheading(): ?string
    {
        return __('larawebkit-themes.manage.subheading');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('manage settings') ?? false;
    }

    /** @return list<array{theme: Theme, active: bool, screenshot: ?string, parent: ?string}> */
    public function getThemes(): array
    {
        $manager = app(ThemeManager::class);
        $active = $manager->active();

        return collect($manager->all())
            ->sortBy(fn (Theme $t) => [$t->name !== $active, $t->name === ThemeManager::FALLBACK, $t->label()])
            ->map(fn (Theme $t) => [
                'theme' => $t,
                'active' => $t->name === $active,
                'screenshot' => $t->screenshot() ? route('larawebkit.theme-asset', ['theme' => $t->name, 'path' => basename($t->screenshot()), 'v' => filemtime($t->screenshot())]) : null,
                'parent' => $t->parent() ? ($manager->get($t->parent())?->label() ?? $t->parent()) : null,
            ])->values()->all();
    }

    public function activate(string $name): void
    {
        $manager = app(ThemeManager::class);
        if (! $manager->exists($name)) {
            return;
        }
        $manager->activate($name);
        Notification::make()->success()->title(__('larawebkit-themes.manage.activated', ['theme' => $manager->get($name)->label()]))->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('upload')
                ->label(__('larawebkit-themes.upload.action'))
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->visible(fn () => ThemeInstaller::enabled() && (auth()->user()?->hasRole('super-admin') ?? false))
                ->modalDescription(__('larawebkit-themes.upload.warning'))
                ->schema([
                    FileUpload::make('archive')
                        ->label(__('larawebkit-themes.upload.file'))
                        ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed'])
                        ->maxSize((int) config('larawebkit-themes.max_upload_mb', 20) * 1024)
                        ->storeFiles(false)
                        ->required(),
                ])
                ->action(function (array $data) {
                    abort_unless(auth()->user()?->hasRole('super-admin'), 403);
                    $file = $data['archive'] ?? null;
                    if (! $file instanceof TemporaryUploadedFile) {
                        return;
                    }
                    try {
                        $theme = app(ThemeInstaller::class)->install($file->getRealPath());
                        Notification::make()->success()->title(__('larawebkit-themes.upload.installed', ['theme' => $theme->label()]))->send();
                    } catch (InvalidArgumentException $e) {
                        Notification::make()->danger()->title(__('larawebkit-themes.upload.failed'))->body($e->getMessage())->persistent()->send();
                    }
                }),
            Action::make('rebuild')
                ->label(__('larawebkit-themes.manage.publish'))
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('gray')
                ->action(function () {
                    foreach (app(ThemeManager::class)->all() as $theme) {
                        app(ThemeAssets::class)->publish($theme);
                    }
                    Notification::make()->success()->title(__('larawebkit-themes.manage.published'))->send();
                }),
        ];
    }
}
