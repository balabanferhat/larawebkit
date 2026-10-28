<?php

namespace App\Modules\Ai\Jobs;

use App\Models\User;
use App\Modules\Ai\AiManager;
use App\Modules\Ai\Models\AiTask;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/** Long AI jobs (drafts) run on the database queue; the editor polls the task. */
class RunAiTask implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(public AiTask $task) {}

    public function handle(AiManager $ai): void
    {
        $this->task->update(['status' => 'running']);

        try {
            $output = $ai->run($this->task->action, $this->task->input, User::find($this->task->user_id), $this->task->input['locale'] ?? null);
            $this->task->update(['status' => 'done', 'output' => $output]);
        } catch (Throwable $e) {
            $this->task->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 1000)]);
        }
    }
}
