<?php

use App\Modules\Editor\Scoring\ContentInput;
use App\Modules\Editor\Scoring\GeoChecker;
use App\Modules\Editor\Scoring\HumanScorer;
use App\Modules\Editor\Scoring\SeoScorer;
use App\Modules\Editor\Scoring\TextAnalyzer;

function seoInput(array $overrides = []): ContentInput
{
    return ContentInput::fromArray($overrides + ['title' => 'T', 'content' => '', 'keywords' => [], 'site_host' => 'example.com']);
}

function words(int $n, string $word = 'word'): string
{
    return implode(' ', array_fill(0, $n, $word));
}

it('matches the shared fixtures (same results as the browser scorer)', function () {
    $expected = json_decode(file_get_contents(base_path('tests/fixtures/scoring/expected.json')), true);
    foreach (json_decode(file_get_contents(base_path('tests/fixtures/scoring/cases.json')), true) as $case) {
        $i = $case['input'];
        $input = new ContentInput($i['title'], $i['html'], $i['metaDescription'], $i['keywords'], $i['locale'], $i['featuredImageHasAlt'], $i['summary'], $i['faq'], $i['sources'], $i['hasAuthor'], $i['siteHost']);
        $compact = fn ($r) => ['score' => $r->score, 'checks' => array_map(fn ($c) => [$c->key, $c->passed, $c->points], $r->checks)];

        expect($compact((new SeoScorer)->score($input)))->toBe($expected[$case['name']]['seo'], $case['name'])
            ->and($compact((new HumanScorer)->score($input)))->toBe($expected[$case['name']]['human'], $case['name'])
            ->and($compact((new GeoChecker)->score($input)))->toBe($expected[$case['name']]['geo'], $case['name']);
    }
});

it('extracts words and sentences from html', function () {
    $a = new TextAnalyzer('<h2>Hello world</h2><p>It&rsquo;s fine. Really? Yes!</p><ul><li>One item</li></ul>');

    expect($a->words)->toBe(['hello', 'world', "it's", 'fine', 'really', 'yes', 'one', 'item'])
        ->and($a->sentences)->toBe(['Hello world', 'It’s fine.', 'Really?', 'Yes!', 'One item']);
});

it('scores word count tiers 20 / 12 / 5', function () {
    $points = fn (int $n) => (new SeoScorer)->score(seoInput(['content' => '<p>'.words($n).'</p>']))->check('word_count')->points;

    expect($points(800))->toBe(20)->and($points(500))->toBe(12)->and($points(499))->toBe(5);
});

it('scores the meta description 15 in range, 8 out of range, 0 missing', function () {
    $points = fn (string $m) => (new SeoScorer)->score(seoInput(['meta_description' => $m]))->check('meta_description')->points;

    expect($points(str_repeat('a', 120)))->toBe(15)->and($points(str_repeat('a', 160)))->toBe(15)
        ->and($points(str_repeat('a', 119)))->toBe(8)->and($points(str_repeat('a', 161)))->toBe(8)->and($points(''))->toBe(0);
});

it('scores H2 count 15 for 4+, 10 for 2-3, 0 otherwise', function () {
    $points = fn (int $n) => (new SeoScorer)->score(seoInput(['content' => str_repeat('<h2>x</h2>', $n)]))->check('h2_count')->points;

    expect($points(4))->toBe(15)->and($points(3))->toBe(10)->and($points(2))->toBe(10)->and($points(1))->toBe(0);
});

it('scores keyword in title and density 1-3%', function () {
    $content = '<p>'.words(98).' seo tips</p>'; // 100 words, "seo tips" once = 2%
    $r = (new SeoScorer)->score(seoInput(['title' => 'Best SEO tips', 'content' => $content, 'keywords' => ['seo tips']]));

    expect($r->check('keyword_in_title')->points)->toBe(15)
        ->and($r->check('keyword_density')->points)->toBe(15)
        ->and($r->meta['density'])->toBe(2.0);

    $stuffed = (new SeoScorer)->score(seoInput(['content' => '<p>'.words(10, 'seo').'</p>', 'keywords' => ['seo']]));
    expect($stuffed->check('keyword_density')->points)->toBe(8);
});

it('awards list, bold, image alt and internal link checks', function () {
    $r = (new SeoScorer)->score(seoInput(['content' => '<ul><li>a</li></ul><strong>b</strong><img src="/a.webp" alt="A chart"><a href="https://www.example.com/x">x</a>']));

    expect($r->check('has_list')->passed)->toBeTrue()
        ->and($r->check('has_bold')->passed)->toBeTrue()
        ->and($r->check('image_alt')->passed)->toBeTrue()
        ->and($r->check('internal_link')->passed)->toBeTrue();

    $external = (new SeoScorer)->score(seoInput(['content' => '<a href="https://other.org">x</a><img src="a" alt="">']));
    expect($external->check('internal_link')->passed)->toBeFalse()->and($external->check('image_alt')->passed)->toBeFalse();
});

it('normalises the SEO score to 0-100 when weights change', function () {
    $scorer = new SeoScorer(['has_bold' => ['enabled' => true, 'points' => 7], 'has_list' => ['enabled' => true, 'points' => 3]]);

    expect($scorer->score(seoInput(['content' => '<b>x</b>']))->score)->toBe(70);
});

it('penalises uniform sentence lengths', function () {
    $uniform = '<p>'.str_repeat('This sentence has exactly six words. ', 6).'</p>';
    $r = (new HumanScorer)->score(ContentInput::fromArray(['content' => $uniform, 'locale' => 'en']), []);

    expect($r->check('variation')->points)->toBe(-25)->and($r->meta['cv'])->toBe(0.0);
});

it('penalises long sentences, clichés (max 35) and repeated openings (max 15)', function () {
    $long = '<p>'.words(40).'. '.words(35).'.</p>';
    expect((new HumanScorer)->score(ContentInput::fromArray(['content' => $long]), [])->check('long_sentences')->points)->toBe(-10);

    $cliches = array_map(fn ($i) => "phrase$i", range(1, 10));
    $text = '<p>'.implode('. ', $cliches).'.</p>';
    expect((new HumanScorer)->score(ContentInput::fromArray(['content' => $text]), $cliches)->check('cliches')->points)->toBe(-35);

    $starts = '<p>'.str_repeat('We go. We run. We stop. Then rest. ', 5).'</p>';
    expect((new HumanScorer)->score(ContentInput::fromArray(['content' => $starts]), [])->check('repeated_starts')->points)->toBe(-15);
});

it('adds bonuses for questions and addressing the reader, per locale', function () {
    $en = (new HumanScorer)->score(ContentInput::fromArray(['content' => '<p>Do you like it? Yes. And a much longer sentence follows right after it, with many more words than before.</p>', 'locale' => 'en']), []);
    expect($en->check('question')->points)->toBe(3)->and($en->check('reader')->points)->toBe(4)->and($en->score)->toBe(100);

    $tr = (new HumanScorer)->score(ContentInput::fromArray(['content' => '<p>Sizin için yazdık.</p>', 'locale' => 'tr']), []);
    expect($tr->check('reader')->passed)->toBeTrue();
});

it('ships cliché lists for en and tr in config files', function () {
    expect(HumanScorer::clichesFor('en'))->toContain('delve into')
        ->and(HumanScorer::clichesFor('tr'))->toContain('sonuç olarak');
});

it('runs the GEO checks', function () {
    $r = (new GeoChecker)->score(ContentInput::fromArray([
        'content' => '<p>'.words(30).'</p><h2>What is it?</h2><table><tr><td>x</td></tr></table>',
        'summary' => 'Short answer.', 'sources' => 'https://example.org', 'has_author' => true,
    ]));

    expect($r->score)->toBe(100);
    expect((new GeoChecker)->score(ContentInput::fromArray(['content' => '<p>'.words(200).'</p>']))->check('answer_first')->passed)->toBeFalse();
});
