<?php

namespace App\Modules\Backup\Console;

use App\Modules\Backup\BackupManager;
use Illuminate\Console\Command;
use Illuminate\Support\Number;
use Throwable;

class BackupCommand extends Command
{
    protected $signature = 'larawebkit:backup
        {--no-files : Only the database}
        {--no-upload : Do not copy to the remote disk}
        {--list : List the backups}';

    protected $description = 'Back up the database and uploaded files into one zip (with retention and an optional remote copy)';

    public function handle(BackupManager $backups): int
    {
        if ($this->option('list')) {
            $this->table(['Backup', 'Size', 'Date'], array_map(fn ($b) => [$b['name'], Number::fileSize($b['size'], 1), date('Y-m-d H:i', $b['date'])], $backups->list()));

            return self::SUCCESS;
        }

        try {
            $result = $backups->create(! $this->option('no-files'), ! $this->option('no-upload'));
        } catch (Throwable $e) {
            $this->components->error('Backup failed: '.$e->getMessage());

            return self::FAILURE;
        }
        $this->components->info("Backup {$result['name']} (".Number::fileSize($result['size'], 1).", {$result['files']} files)".($result['remote'] ? ", copied to {$result['remote']}" : ''));
        foreach ($result['warnings'] as $warning) {
            $this->components->warn($warning);
        }

        return self::SUCCESS;
    }
}
