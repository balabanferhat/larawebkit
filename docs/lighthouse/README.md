# Lighthouse reports

Measured with Lighthouse 13.6.0 (mobile emulation, simulated slow 4G and 4× CPU throttling, the defaults) against
`larawebkit:demo {theme}` on a fresh install, served by `php artisan serve` with `APP_ENV=production`,
`APP_DEBUG=false` and the full-page cache on. Home page and the first post of each demo.

| Theme | Page | Performance | Accessibility | Best practices | SEO | FCP | LCP | CLS | Page weight |
|---|---|---|---|---|---|---|---|---|---|
| business | [home](business-home.report.html) `/` | 99 | 100 | 100 | 100 | 1.2 s | 2.1 s | 0 | Total size was 187 KiB |
| business | [post](business-post.report.html) `/blog/five-questions-to-answer-before-you-redesign` | 99 | 100 | 100 | 100 | 1.1 s | 2.0 s | 0 | Total size was 189 KiB |
| personal | [home](personal-home.report.html) `/` | 99 | 100 | 100 | 100 | 1.1 s | 2.0 s | 0 | Total size was 182 KiB |
| personal | [post](personal-post.report.html) `/blog/the-first-90-days-as-a-new-manager` | 99 | 100 | 100 | 100 | 1.1 s | 2.0 s | 0 | Total size was 196 KiB |
| magazine | [home](magazine-home.report.html) `/` | 97 | 100 | 100 | 100 | 1.3 s | 2.6 s | 0 | Total size was 268 KiB |
| magazine | [post](magazine-post.report.html) `/blog/the-return-of-the-slow-weekend` | 98 | 100 | 100 | 100 | 1.2 s | 2.4 s | 0 | Total size was 231 KiB |
| docs | [home](docs-home.report.html) `/` | 100 | 100 | 100 | 100 | 1.1 s | 1.7 s | 0 | Total size was 128 KiB |
| docs | [post](docs-post.report.html) `/blog/orbit-20-faster-builds-and-a-new-config-format` | 100 | 100 | 100 | 100 | 1.1 s | 1.8 s | 0 | Total size was 142 KiB |

The magazine rows were measured again for v0.3 (front page and archives built from blocks).

Open a `.report.html` file in a browser for the full report.

## Budgets in CI

The **Lighthouse budgets** CI job builds every theme's demo on a production-like install and measures the
home page, the first post and the blog with `scripts/lighthouse/run.mjs`. It fails when a page drops below
the scores or goes over the transfer sizes in [`scripts/lighthouse/budgets.json`](../../scripts/lighthouse/budgets.json)
(performance ≥ 90, accessibility, best practices and SEO 100; document ≤ 100 KB, CSS ≤ 80 KB, JS ≤ 20 KB, fonts
≤ 150 KB, images ≤ 300 KB, total ≤ 500 KB uncompressed). Performance is the best of up to three runs, so one
slow run on a busy runner does not fail the build. The reports are attached to the run as an artifact and
the table appears in the job summary.

Locally, with the site running:

```bash
node scripts/lighthouse/run.mjs                      # all themes (replaces the demo content each time)
node scripts/lighthouse/run.mjs --themes=business    # one theme
node scripts/lighthouse/run.mjs --no-demo --themes=mysite   # your own content, no demo
```

Reports land in `storage/lighthouse/` (ignored by git). Change a budget in the JSON file when a design really
needs more, and say why in the pull request.

## Reproduce one report

```bash
php artisan migrate:fresh --force && php artisan larawebkit:install
php artisan larawebkit:demo business
APP_ENV=production APP_DEBUG=false php artisan serve
npx lighthouse http://localhost:8000/ --view
```

Numbers from `php artisan serve` on a development machine; real hosting adds network latency but also serves
static files (CSS, fonts, WebP) directly from the web server with long cache headers (`public/.htaccess`).
