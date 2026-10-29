<?php

namespace App\Modules\Ai\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Ai\AiManager;
use App\Modules\Ai\Jobs\RunAiTask;
use App\Modules\Ai\Models\AiTask;
use App\Modules\Content\Models\Post;
use App\Modules\Editor\Scoring\TextAnalyzer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

/**
 * Editor endpoints for the AI writing assistant. Short actions answer
 * directly; long ones (draft) are queued and polled via /tasks/{uuid}.
 */
class AiController extends Controller
{
    public function __construct(private AiManager $ai) {}

    public function run(Request $request, string $action): JsonResponse
    {
        abort_unless($request->user()?->can('use ai'), 403);
        if (! $this->ai->available($action)) {
            return response()->json(['message' => $this->ai->overBudget() ? __('larawebkit-ai.errors.budget') : __('larawebkit-ai.errors.disabled')], 422);
        }

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:300'],
            'brief' => ['nullable', 'string', 'max:5000'],
            'keywords' => ['nullable'],
            'text' => ['nullable', 'string', 'max:100000'],
            'instruction' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:2000'],
            'locale' => ['nullable', 'string', 'max:10'],
        ]);
        $data['keywords'] = implode(', ', array_filter((array) ($data['keywords'] ?? [])));
        // Send plain text to the model for analysis actions; keep HTML for rewrites.
        if ($action !== 'rewrite' && isset($data['text'])) {
            $data['text'] = Str::limit(TextAnalyzer::htmlToText($data['text']), 12000);
        }
        $data['instruction'] ??= __('larawebkit-ai.default_instruction');
        if ($action === 'topics') {
            $data['description'] ??= (string) setting('general.tagline');
            $data['existing'] = Post::latest()->limit(30)->pluck('title')->implode("\n");
        }
        $locale = $data['locale'] ?? app()->getLocale();

        if ($this->ai->queued($action)) {
            $task = AiTask::create(['uuid' => (string) Str::uuid(), 'action' => $action, 'status' => 'pending', 'input' => $data + ['locale' => $locale], 'user_id' => $request->user()->getKey()]);
            RunAiTask::dispatch($task);

            return response()->json(['task' => $task->uuid, 'status' => $task->fresh()->status, 'output' => $task->fresh()->output], 202);
        }

        try {
            return response()->json(['output' => $this->ai->run($action, $data, $request->user(), $locale)]);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }
    }

    public function task(Request $request, AiTask $task): JsonResponse
    {
        abort_unless($task->user_id === $request->user()?->getKey(), 404);

        return response()->json(['task' => $task->uuid, 'status' => $task->status, 'output' => $task->output, 'error' => $task->error]);
    }
}
