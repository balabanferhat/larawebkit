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
| magazine | [home](magazine-home.report.html) `/` | 98 | 100 | 100 | 100 | 1.2 s | 2.4 s | 0 | Total size was 264 KiB |
| magazine | [post](magazine-post.report.html) `/blog/the-return-of-the-slow-weekend` | 98 | 100 | 100 | 100 | 1.3 s | 2.3 s | 0 | Total size was 220 KiB |
| docs | [home](docs-home.report.html) `/` | 100 | 100 | 100 | 100 | 1.1 s | 1.7 s | 0 | Total size was 128 KiB |
| docs | [post](docs-post.report.html) `/blog/orbit-20-faster-builds-and-a-new-config-format` | 100 | 100 | 100 | 100 | 1.1 s | 1.8 s | 0 | Total size was 142 KiB |

Open a `.report.html` file in a browser for the full report. To reproduce:

```bash
php artisan migrate:fresh --force && php artisan larawebkit:install
php artisan larawebkit:demo business
APP_ENV=production APP_DEBUG=false php artisan serve
npx lighthouse http://localhost:8000/ --view
```

Numbers from `php artisan serve` on a development machine; real hosting adds network latency but also serves
static files (CSS, fonts, WebP) directly from the web server with long cache headers (`public/.htaccess`).
