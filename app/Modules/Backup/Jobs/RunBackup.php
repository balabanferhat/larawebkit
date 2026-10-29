<?php

namespace App\Modules\Backup\Jobs;

use App\Modules\Backup\BackupManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/** "Back up now" from the admin: runs on the queue (cron), so a large site does not hit the web timeout. */
class RunBackup implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, Queueable;

    public int $timeout = 1800;

    public int $tries = 1;

    public function handle(BackupManager $backups): void
    {
        $backups->create();
    }
}
