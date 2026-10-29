<?php

namespace App\Modules\Editor;

use App\Modules\Blocks\BlockRegistry;
use App\Modules\Core\ModuleRegistry;
use App\Modules\Editor\Scoring\ContentInput;
use App\Modules\Editor\Scoring\GeoChecker;
use App\Modules\Editor\Scoring\HumanScorer;
use App\Modules\Editor\Scoring\ScoreResult;
use App\Modules\Editor\Scoring\SeoScorer;
use App\Modules\Media\Models\Media;
use Illuminate\Database\Eloquent\Model;

/**
 * Authoritative (server-side) scores for a page/post or for raw form state.
 * The browser runs the same algorithm live; this one is stored on save.
 */
class ContentScorer
{
    /** @return array{seo: ScoreResult, human: ScoreResult, geo: ScoreResult} */
    public function score(ContentInput $input): array
    {
        return [
            'seo' => (new SeoScorer)->score($input),
            'human' => (new HumanScorer)->score($input),
            'geo' => (new GeoChecker)->score($input),
        ];
    }

    public function inputFromModel(Model $model): ContentInput
    {
        return $this->inputFromState([
            'keywords' => $model->keywords,
            'faq' => $model->faq,
            'blocks' => $model->getAttribute('blocks'),
        ] + $model->getAttributes());
    }

    /** @param  array<string, mixed>  $state  form state or model attributes */
    public function inputFromState(array $state): ContentInput
    {
        $featuredAlt = false;
        if (! empty($state['featured_media_id'])) {
            $featuredAlt = filled(Media::whereKey($state['featured_media_id'])->value('alt'));
        }

        $faq = $state['faq'] ?? [];
        if (is_string($faq)) {
            $faq = json_decode($faq, true) ?: [];
        }
        $keywords = $state['keywords'] ?? [];
        if (is_string($keywords)) {
            $keywords = json_decode($keywords, true) ?: explode(',', $keywords);
        }

        $content = is_string($state['content'] ?? null) ? $state['content'] : '';
        // Pages built from section blocks: their text counts like editor content.
        if (! empty($state['blocks']) && ModuleRegistry::enabled('blocks')) {
            $content = app(BlockRegistry::class)->html($state['blocks']).$content;
            $faq = [...array_values((array) $faq), ...app(BlockRegistry::class)->faq($state['blocks'])];
        }

        return ContentInput::fromArray([
            'title' => $state['title'] ?? '',
            'content' => $content,
            'meta_description' => $state['meta_description'] ?? '',
            'keywords' => $keywords,
            'locale' => $state['locale'] ?? app()->getLocale(),
            'featured_image_has_alt' => $featuredAlt,
            'summary' => $state['summary'] ?? '',
            'faq' => array_values((array) $faq),
            'sources' => $state['sources'] ?? '',
            'has_author' => ! empty($state['author_id']),
        ]);
    }

    /** Store the three scores on the model (called while saving). */
    public function apply(Model $model): void
    {
        $scores = $this->score($this->inputFromModel($model));
        $model->seo_score = $scores['seo']->score;
        $model->human_score = $scores['human']->score;
        $model->geo_score = $scores['geo']->score;
    }

    /** Config + translations handed to resources/js/larawebkit/scoring.js. */
    public static function clientConfig(?string $locale = null): array
    {
        $locale ??= app()->getLocale();

        return [
            'seo' => config('larawebkit-editor.seo'),
            'human' => config('larawebkit-editor.human'),
            'geo' => config('larawebkit-editor.geo'),
            'gate' => app(PublishGate::class)->thresholds(),
            'cliches' => collect(array_keys((array) config('larawebkit.locales')))->mapWithKeys(fn ($l) => [$l => HumanScorer::clichesFor($l)])->all(),
            'lang' => trans('larawebkit-editor', [], $locale),
            'siteHost' => parse_url((string) config('app.url'), PHP_URL_HOST) ?: '',
        ];
    }
}
