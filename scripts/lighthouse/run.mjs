#!/usr/bin/env node
/*
 * Lighthouse against the demo sites, with budgets (scripts/lighthouse/budgets.json).
 *
 *   php artisan serve &                       # or any running copy of the site
 *   node scripts/lighthouse/run.mjs           # every bundled theme: home, a post, the blog
 *   node scripts/lighthouse/run.mjs --themes=business --base=http://127.0.0.1:8000 --out=storage/lighthouse
 *   node scripts/lighthouse/run.mjs --no-demo # measure the site as it is (no demo content)
 *
 * Builds each theme's demo (`php artisan larawebkit:demo {theme} --force`), runs Lighthouse (mobile,
 * default throttling) and fails when a score or a transfer size is outside the budget. Performance is
 * the best of up to three runs: one slow run on a busy machine is noise, three are a regression.
 * Needs Chrome or Chromium (CHROME_PATH) and network access for `npx lighthouse` on the first run.
 */
import { execFileSync } from 'node:child_process';
import { mkdirSync, readFileSync, writeFileSync, appendFileSync, existsSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const here = dirname(fileURLToPath(import.meta.url));
const args = Object.fromEntries(process.argv.slice(2).map((a) => {
    const [k, v] = a.replace(/^--/, '').split('=');
    return [k, v ?? true];
}));
const base = (args.base || 'http://127.0.0.1:8000').replace(/\/$/, '');
const out = args.out || 'storage/lighthouse';
const themes = (args.themes || 'business,personal,magazine,docs').split(',').filter(Boolean);
const budgets = JSON.parse(readFileSync(join(here, 'budgets.json'), 'utf8'));
const lighthouse = process.env.LIGHTHOUSE_BIN || 'npx';
const lighthouseArgs = process.env.LIGHTHOUSE_BIN ? [] : ['--yes', 'lighthouse@13.6.0'];
mkdirSync(out, { recursive: true });

function artisan(...params) {
    return execFileSync('php', ['artisan', ...params], { encoding: 'utf8', stdio: ['ignore', 'pipe', 'inherit'] });
}

async function pagesFor(theme) {
    const home = await (await fetch(base + '/')).text();
    const post = home.match(/href="((?:\/[a-z]{2})?\/blog\/(?!category|tag)[a-z0-9-]+)"/)?.[1];
    return [['home', '/'], ...(post ? [['post', post]] : []), ['blog', '/blog']].map(([name, path]) => ({ name: `${theme}-${name}`, url: base + path }));
}

function run(page) {
    const target = join(out, page.name);
    for (let attempt = 1; attempt <= 3; attempt++) {
        try {
            execFileSync(lighthouse, [...lighthouseArgs, page.url, '--quiet', '--output=json', '--output=html', `--output-path=${target}`,
                '--chrome-flags=--headless=new --no-sandbox'], { stdio: 'ignore', timeout: 240000 });
        } catch {
            // retried below
        }
        if (existsSync(`${target}.report.json`)) {
            const report = JSON.parse(readFileSync(`${target}.report.json`, 'utf8'));
            if (!report.runtimeError) return report;
        }
    }
    throw new Error(`Lighthouse could not measure ${page.url}`);
}

function measure(report) {
    const scores = Object.fromEntries(Object.entries(report.categories).map(([k, v]) => [k, Math.round((v.score ?? 0) * 100)]));
    const sizes = { total: 0 };
    for (const item of report.audits['network-requests']?.details?.items ?? []) {
        const type = item.resourceType || 'Other';
        sizes[type] = (sizes[type] ?? 0) + (item.transferSize ?? 0) / 1024;
        sizes.total += (item.transferSize ?? 0) / 1024;
    }
    return { scores, sizes };
}

function failures({ scores, sizes }) {
    const list = [];
    for (const [category, min] of Object.entries(budgets.categories)) {
        if ((scores[category] ?? 0) < min) list.push(`${category} ${scores[category]} < ${min}`);
    }
    for (const [type, max] of Object.entries(budgets.resources)) {
        if ((sizes[type] ?? 0) > max) list.push(`${type} ${sizes[type].toFixed(0)} KB > ${max} KB`);
    }
    return list;
}

const rows = [];
let failed = false;
for (const theme of themes) {
    if (!args['no-demo']) {
        artisan('larawebkit:demo', theme, '--force');
    }
    for (const page of await pagesFor(theme)) {
        let best = measure(run(page));
        for (let i = 0; i < 2 && best.scores.performance < budgets.categories.performance; i++) {
            const again = measure(run(page));
            if (again.scores.performance > best.scores.performance) best = again;
        }
        const problems = failures(best);
        failed ||= problems.length > 0;
        rows.push({ page: page.name, ...best.scores, kb: Math.round(best.sizes.total), result: problems.length ? '✘ ' + problems.join('; ') : '✔' });
        console.log(`${problems.length ? '✘' : '✔'} ${page.name}`, best.scores, `${Math.round(best.sizes.total)} KB`, problems.join('; '));
    }
}

const header = '| Page | Performance | Accessibility | Best practices | SEO | KB | |\n|---|---|---|---|---|---|---|\n';
const table = header + rows.map((r) => `| ${r.page} | ${r.performance} | ${r.accessibility} | ${r['best-practices']} | ${r.seo} | ${r.kb} | ${r.result} |`).join('\n') + '\n';
writeFileSync(join(out, 'summary.md'), table);
if (process.env.GITHUB_STEP_SUMMARY) appendFileSync(process.env.GITHUB_STEP_SUMMARY, '## Lighthouse\n\n' + table);
console.log('\n' + table);
process.exit(failed ? 1 : 0);
