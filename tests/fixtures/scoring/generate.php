<?php

/*
 * Regenerates tests/fixtures/scoring/expected.json from the PHP scorers.
 * Run after an intentional scoring change: php tests/fixtures/scoring/generate.php
 * Both the Pest suite and `node --test tests/js` then check against it.
 */

use App\Modules\Editor\Scoring\ContentInput;
use App\Modules\Editor\Scoring\GeoChecker;
use App\Modules\Editor\Scoring\HumanScorer;
use App\Modules\Editor\Scoring\SeoScorer;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../../../vendor/autoload.php';
$app = require __DIR__.'/../../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

echo json_encode(scoring_expected(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL;

function scoring_expected(): array
{
    $out = [];
    foreach (json_decode(file_get_contents(__DIR__.'/cases.json'), true) as $case) {
        $i = $case['input'];
        $input = new ContentInput($i['title'], $i['html'], $i['metaDescription'], $i['keywords'], $i['locale'], $i['featuredImageHasAlt'], $i['summary'], $i['faq'], $i['sources'], $i['hasAuthor'], $i['siteHost']);
        $compact = fn ($r) => ['score' => $r->score, 'checks' => array_map(fn ($c) => [$c->key, $c->passed, $c->points], $r->checks)];
        $out[$case['name']] = [
            'seo' => $compact((new SeoScorer)->score($input)),
            'human' => $compact((new HumanScorer)->score($input)),
            'geo' => $compact((new GeoChecker)->score($input)),
        ];
    }

    return $out;
}
