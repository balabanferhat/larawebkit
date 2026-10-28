// Runs the browser scorer against the same fixtures as the PHP scorer: node --test tests/js
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import '../../resources/js/larawebkit/scoring.js';

const cases = JSON.parse(readFileSync(new URL('../fixtures/scoring/cases.json', import.meta.url)));
const expected = JSON.parse(readFileSync(new URL('../fixtures/scoring/expected.json', import.meta.url)));
const config = JSON.parse(readFileSync(new URL('../fixtures/scoring/config.json', import.meta.url)));
const S = globalThis.LarawebkitScoring;
const compact = (r) => ({ score: r.score, checks: r.checks.map((c) => [c.key, c.passed, c.points]) });

for (const c of cases) {
    test(`scores match the PHP scorer: ${c.name}`, () => {
        const i = c.input;
        assert.deepEqual(compact(S.seoScore(i, config.seo, {})), expected[c.name].seo);
        assert.deepEqual(compact(S.humanScore(i, config.human, config.cliches[i.locale] || [], {})), expected[c.name].human);
        assert.deepEqual(compact(S.geoScore(i, config.geo, {})), expected[c.name].geo);
    });
}
