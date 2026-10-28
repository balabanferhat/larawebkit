<?php

namespace App\Modules\Logs\Console;

use App\Modules\Logs\Models\AccessLog;
use App\Modules\Logs\Models\AuthLog;
use Illuminate\Console\Command;

class PruneLogsCommand extends Command
{
    protected $signature = 'larawebkit:logs:prune {--days= : Override the retention setting for the access log}';

    protected $description = 'Delete access log rows older than the retention setting (and auth logs older than a year).';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: setting('privacy.access_log_retention_days', 90));
        $access = AccessLog::where('created_at', '<', now()->subDays(max(1, $days)))->delete();
        $auth = AuthLog::where('created_at', '<', now()->subYear())->delete();

        $this->info(__('larawebkit-logs.pruned', ['access' => $access, 'auth' => $auth]));

        return self::SUCCESS;
    }
}
