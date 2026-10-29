<?php

namespace App\Modules\Editor\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Ai\AiManager;
use App\Modules\Editor\Pexels\PexelsClient;
use App\Modules\Media\Http\Controllers\MediaApiController;
use App\Modules\Media\Models\Media;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class PexelsController extends Controller
{
    public function __construct(private PexelsClient $pexels) {}

    public function search(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('manage media') && $this->pexels->enabled(), 403);
        $request->validate(['q' => ['required', 'string', 'max:100'], 'orientation' => ['nullable', 'in:landscape,portrait,square'], 'page' => ['nullable', 'integer', 'min:1']]);

        try {
            $result = $this->pexels->search($request->string('q')->toString(), $request->input('orientation'), (int) $request->integer('page', 1));
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        // Mark photos already used on this post/page so the same photo is not picked twice.
        $used = $this->usedOn($request);
        $imported = Media::where('source', 'pexels')->whereIn('source_id', array_column($result['photos'], 'id'))->pluck('source_id')->all();
        $result['photos'] = array_map(fn ($p) => $p + ['used_here' => in_array($p['id'], $used, true), 'imported' => in_array($p['id'], $imported, true)], $result['photos']);

        return response()->json($result);
    }

    public function import(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('manage media') && $this->pexels->enabled(), 403);
        $request->validate(['id' => ['required', 'string', 'regex:/^\d+$/']]);

        if (in_array($request->input('id'), $this->usedOn($request), true)) {
            return response()->json(['message' => __('larawebkit-editor.pexels.already_used')], 409);
        }

        try {
            $media = $this->pexels->import($request->input('id'), $request->user()->getKey());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => $e->getMessage()], 502);
        }

        return response()->json(['data' => MediaApiController::present($media, true)], 201);
    }

    /** 3-5 English search keywords for the title (AI when configured, else the title). */
    public function suggest(Request $request, AiManager $ai): JsonResponse
    {
        abort_unless($request->user()?->can('manage media'), 403);
        $title = trim((string) $request->input('title'));
        if ($title === '') {
            return response()->json(['query' => '']);
        }

        $query = $title;
        if ($ai->available('image_keywords') && $request->user()->can('use ai')) {
            try {
                $query = trim($ai->run('image_keywords', ['title' => $title], $request->user())) ?: $title;
            } catch (Throwable $e) {
                report($e);
            }
        }

        return response()->json(['query' => mb_substr(preg_replace('/\s+/', ' ', $query), 0, 100)]);
    }

    /** @return list<string> */
    private function usedOn(Request $request): array
    {
        $type = (string) $request->input('usable_type');
        $id = (int) $request->input('usable_id');
        if ($type === '' || $id === 0 || Relation::getMorphedModel($type) === null) {
            return [];
        }

        return Media::where('source', 'pexels')
            ->whereHas('usages', fn ($q) => $q->where('usable_type', $type)->where('usable_id', $id))
            ->pluck('source_id')->map(fn ($v) => (string) $v)->all();
    }
}
