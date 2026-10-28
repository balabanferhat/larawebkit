<?php

namespace App\Modules\Core\Console;

use App\Modules\Media\ImagePipeline;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Pre-flight check for shared hosting: extensions, writable dirs, .env, cron.
 * Exit code 1 when a required check fails (usable in CI / deploy scripts).
 */
class DeployCheckCommand extends Command
{
    protected $signature = 'larawebkit:deploy-check';

    protected $description = 'Check PHP, extensions, writable directories, .env values and the cron heartbeat.';

    /** @var list<array{0: string, 1: string, 2: string}> */
    private array $rows = [];

    public function handle(): int
    {
        $this->components->info(__('larawebkit.deploy_check.title'));
        $production = app()->environment('production');

        $this->check(version_compare(PHP_VERSION, '8.3.0', '>='), __('larawebkit.deploy_check.php', ['version' => PHP_VERSION, 'required' => '8.3']));
        foreach (['pdo', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json', 'fileinfo', 'intl', 'zip', 'bcmath'] as $ext) {
            $this->check(extension_loaded($ext), __('larawebkit.deploy_check.extension', ['name' => $ext]), in_array($ext, ['intl', 'zip', 'bcmath'], true));
        }

        $image = false;
        try {
            $image = ImagePipeline::available() && app(ImagePipeline::class)->supportsWebp();
        } catch (Throwable) {
        }
        $this->check($image, __('larawebkit.deploy_check.image'), ! ImagePipeline::available() ? false : true);

        foreach ([storage_path(), storage_path('framework/cache'), storage_path('logs'), base_path('bootstrap/cache')] as $path) {
            $this->check(is_writable($path), __('larawebkit.deploy_check.writable', ['path' => str_replace(base_path().'/', '', $path)]));
        }

        $this->check(filled(config('app.key')), __('larawebkit.deploy_check.app_key'));
        $this->check(! ($production && config('app.debug')), __('larawebkit.deploy_check.debug'));
        $url = (string) config('app.url');
        $this->check(filled($url) && (! $production || str_starts_with($url, 'https://')), __('larawebkit.deploy_check.app_url'), ! $production);
        // Only needed when media lives on the "public" disk (MEDIA_DISK=public_uploads writes into public/ directly).
        if (config('larawebkit-media.disk') === 'public') {
            $this->check(is_link(public_path('storage')) || is_dir(public_path('storage')), __('larawebkit.deploy_check.storage_link'), ! $production);
        }

        $dbOk = false;
        try {
            DB::connection()->getPdo();
            $dbOk = true;
        } catch (Throwable) {
        }
        $this->check($dbOk, __('larawebkit.deploy_check.database'));

        if ($dbOk) {
            Artisan::call('migrate:status', ['--pending' => true]);
            $this->check(! str_contains(Artisan::output(), 'Pending'), __('larawebkit.deploy_check.migrations'));
        }

        $this->check(in_array(config('queue.default'), ['database', 'sync'], true), __('larawebkit.deploy_check.queue'), true);

        $file = (string) config('larawebkit.heartbeat_file');
        $last = is_file($file) ? (int) file_get_contents($file) : 0;
        $fresh = $last > 0 && time() - $last < 180;
        $this->check($fresh, __('larawebkit.deploy_check.heartbeat').' — '.($last ? __('larawebkit.deploy_check.heartbeat_stale', ['ago' => Carbon::createFromTimestamp($last)->diffForHumans()]) : __('larawebkit.deploy_check.heartbeat_missing')), ! $production);

        foreach (['PEXELS_API_KEY' => config('services.pexels.key'), 'ANTHROPIC_API_KEY' => config('larawebkit-ai.providers.anthropic.api_key'), 'INDEXNOW_KEY' => config('services.indexnow.key')] as $name => $value) {
            $this->rows[] = [filled($value) ? '<fg=green>✔</>' : '<fg=gray>–</>', __('larawebkit.deploy_check.optional', ['name' => $name]), 'info'];
        }

        $this->table(['', ''], array_map(fn ($r) => [$r[0], $r[1]], $this->rows));
        $failed = count(array_filter($this->rows, fn ($r) => $r[2] === 'fail'));
        $warnings = count(array_filter($this->rows, fn ($r) => $r[2] === 'warn'));

        $failed || $warnings
            ? $this->components->warn(__('larawebkit.deploy_check.summary', ['failed' => $failed, 'warnings' => $warnings]))
            : $this->components->info(__('larawebkit.deploy_check.all_good'));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function check(bool $ok, string $label, bool $warnOnly = false): void
    {
        $this->rows[] = $ok
            ? ['<fg=green>✔</>', $label, 'ok']
            : ($warnOnly ? ['<fg=yellow>!</>', $label, 'warn'] : ['<fg=red>✘</>', $label, 'fail']);
    }
}
