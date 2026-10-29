<?php

use App\Modules\Backup\BackupManager;
use App\Modules\Backup\Filament\Pages\ManageBackups;
use App\Modules\Backup\Jobs\RunBackup;
use App\Modules\Settings\SettingsRepository;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function openBackup(string $name): ZipArchive
{
    $zip = new ZipArchive;
    expect($zip->open(Storage::disk('local')->path('backups/'.$name)))->toBeTrue();

    return $zip;
}

it('writes the database, the uploaded files and a manifest into one zip', function () {
    superAdmin(['email' => 'owner@example.com']);
    Storage::disk('public')->put('media/2026/10/photo.webp', 'webp-bytes');
    Storage::disk('local')->put('forms/brief.pdf', '%PDF');
    Storage::disk('local')->put('livewire-tmp/skip.tmp', 'x');

    $this->artisan('larawebkit:backup')->expectsOutputToContain('Backup ')->assertSuccessful();

    $backup = app(BackupManager::class)->list()[0];
    $zip = openBackup($backup['name']);
    expect($zip->locateName('files/public/media/2026/10/photo.webp'))->not->toBeFalse()
        ->and($zip->locateName('files/local/forms/brief.pdf'))->not->toBeFalse()
        ->and($zip->locateName('files/local/livewire-tmp/skip.tmp'))->toBeFalse()
        ->and(collect(range(0, $zip->numFiles - 1))->map(fn ($i) => $zip->getNameIndex($i))->filter(fn ($n) => str_starts_with($n, 'files/local/backups')))->toBeEmpty()
        ->and(json_decode($zip->getFromName('manifest.json'), true))->toMatchArray(['files' => 2, 'encrypted' => false]);

    // The database copy holds the site's data (a working SQLite file, or SQL for MySQL / MariaDB).
    if ($zip->locateName('database.sql') !== false) {
        expect($zip->getFromName('database.sql'))->toContain('owner@example.com');
    } else {
        $db = tempnam(sys_get_temp_dir(), 'lwk');
        file_put_contents($db, $zip->getFromName('database.sqlite'));
        $pdo = new PDO('sqlite:'.$db);
        expect($pdo->query('select email from users')->fetchColumn())->toBe('owner@example.com');
        unset($pdo);
        @unlink($db);
    }
    expect(BackupManager::lastRun())->toMatchArray(['ok' => true, 'name' => $backup['name']]);
});

it('keeps only the newest backups and can skip the files', function () {
    app(SettingsRepository::class)->set('backup.keep', 2);
    foreach (range(1, 3) as $i) {
        $this->travel(1)->minutes();
        $this->artisan('larawebkit:backup', ['--no-files' => true])->assertSuccessful();
    }

    $list = app(BackupManager::class)->list();
    expect($list)->toHaveCount(2)
        ->and(openBackup($list[0]['name'])->locateName('manifest.json'))->not->toBeFalse()
        ->and(json_decode(openBackup($list[0]['name'])->getFromName('manifest.json'), true)['files'])->toBe(0);
    $this->artisan('larawebkit:backup', ['--list' => true])->expectsOutputToContain($list[0]['name'])->assertSuccessful();
});

it('copies to a remote disk, encrypts with a password and survives a broken remote', function () {
    Storage::fake('backup-remote');
    config(['larawebkit-backup.remote_disk' => 'backup-remote', 'larawebkit-backup.password' => 'secret-pass']);

    $result = app(BackupManager::class)->create(withFiles: false);
    expect($result['remote'])->toBe('backup-remote:larawebkit-backups/'.$result['name']);
    Storage::disk('backup-remote')->assertExists('larawebkit-backups/'.$result['name']);
    $zip = openBackup($result['name']);
    $entry = json_decode($zip->getFromName('manifest.json'), true)['database']['file'];
    expect($zip->statName($entry)['encryption_method'])->toBe(ZipArchive::EM_AES_256);

    config(['larawebkit-backup.remote_disk' => 'does-not-exist']);
    $this->travel(1)->minutes();
    $result = app(BackupManager::class)->create(withFiles: false);
    expect($result['remote'])->toBeNull()->and($result['warnings'][0])->toContain('Remote copy failed');
});

it('refuses file names outside the backup folder', function () {
    expect(app(BackupManager::class)->path('../../.env'))->toBeNull()
        ->and(app(BackupManager::class)->path('x/../y.zip'))->toBeNull()
        ->and(app(BackupManager::class)->delete('../database.sqlite'))->toBeFalse();
});

it('lists, downloads, deletes and queues backups in the admin for super-admins only', function () {
    $name = app(BackupManager::class)->create(withFiles: false)['name'];
    Queue::fake();

    $this->actingAs(editor());
    $this->get(ManageBackups::getUrl())->assertForbidden();

    $this->actingAs(superAdmin());
    $this->get(ManageBackups::getUrl())->assertOk()->assertSee($name);
    Livewire::test(ManageBackups::class)->callAction('backupNow');
    Queue::assertPushed(RunBackup::class);
    Livewire::test(ManageBackups::class)->callAction('download', arguments: ['name' => $name])->assertFileDownloaded($name);
    Livewire::test(ManageBackups::class)->callAction('delete', arguments: ['name' => $name]);
    expect(app(BackupManager::class)->list())->toBe([]);
});

it('schedules the backup from the settings', function () {
    $events = collect(app(Schedule::class)->events())->filter(fn ($e) => str_contains((string) $e->command, 'larawebkit:backup'));

    expect($events)->toHaveCount(1)->and($events->first()->expression)->toBe('0 3 * * *');
});

it('dumps MySQL / MariaDB as plain SQL', function () {
    superAdmin(['email' => "o'neil@example.com"]);

    $name = app(BackupManager::class)->create(withFiles: false)['name'];

    $sql = openBackup($name)->getFromName('database.sql');
    expect($sql)->toContain('CREATE TABLE `users`')->toContain('SET FOREIGN_KEY_CHECKS=0')->toContain("o\\'neil@example.com");
})->skip(fn () => ! in_array(config('database.connections.'.config('database.default').'.driver'), ['mysql', 'mariadb'], true), 'needs MySQL or MariaDB (CI job "Backups on MariaDB")');
