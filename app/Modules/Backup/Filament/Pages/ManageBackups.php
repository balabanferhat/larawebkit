<?php

namespace App\Modules\Backup\Filament\Pages;

use App\Modules\Backup\BackupManager;
use App\Modules\Backup\Jobs\RunBackup;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use UnitEnum;

/** System → Backups: list, download, delete, back up now. Super-admins only (archives hold every record). */
class ManageBackups extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static ?int $navigationSort = 92;

    protected string $view = 'admin.backups';

    protected static ?string $slug = 'backups';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('larawebkit.nav.system');
    }

    public static function getNavigationLabel(): string
    {
        return __('larawebkit-backup.title');
    }

    public function getTitle(): string
    {
        return __('larawebkit-backup.title');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super-admin') ?? false;
    }

    /** @return list<array{name: string, size: int, date: int}> */
    public function getBackups(): array
    {
        return app(BackupManager::class)->list();
    }

    /** @return array<string, mixed>|null */
    public function getLastRun(): ?array
    {
        return BackupManager::lastRun();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('backupNow')->label(__('larawebkit-backup.now'))->icon('heroicon-o-arrow-down-on-square-stack')
                ->action(function () {
                    RunBackup::dispatch();
                    Notification::make()->success()->title(__('larawebkit-backup.queued'))->body(__('larawebkit-backup.queued_body'))->send();
                }),
        ];
    }

    public function downloadAction(): Action
    {
        return Action::make('download')->label(__('larawebkit-backup.download'))->icon('heroicon-o-arrow-down-tray')->link()
            ->action(function (array $arguments): ?BinaryFileResponse {
                $path = app(BackupManager::class)->path((string) ($arguments['name'] ?? ''));
                if (! $path) {
                    Notification::make()->danger()->title(__('larawebkit-backup.missing'))->send();

                    return null;
                }

                return response()->download($path);
            });
    }

    public function deleteAction(): Action
    {
        return Action::make('delete')->label(__('larawebkit.actions.delete'))->icon('heroicon-o-trash')->color('danger')->link()
            ->requiresConfirmation()
            ->modalDescription(fn (array $arguments) => __('larawebkit-backup.delete_confirm', ['name' => $arguments['name'] ?? '']))
            ->action(function (array $arguments) {
                app(BackupManager::class)->delete((string) ($arguments['name'] ?? ''));
                Notification::make()->success()->title(__('larawebkit-backup.deleted'))->send();
            });
    }
}
