# Deploying to shared hosting (cPanel)

larawebkit runs on plain shared hosting: PHP 8.3+, MySQL/MariaDB, cron. No Redis, no Node, no
long-running worker in production.

## 1. Build locally (or in CI)

Production servers do not need Node. Build the front-end assets on your machine or in CI and upload them:

```bash
composer install --no-dev --optimize-autoloader
npm ci --ignore-scripts && npm run build   # creates public/build
php artisan filament:assets                # admin assets in public/js, public/css
```

Without `public/build` the site still works: themes fall back to inlined base CSS.

## 2. Upload and point the document root

1. Upload the project (everything except `node_modules`, `.env`, `storage/logs/*`) to e.g. `~/larawebkit`.
2. In cPanel → **Domains**, set the document root of the domain to `~/larawebkit/public`.
   If the host does not allow changing it, symlink `public_html` to `larawebkit/public`
   (`rm -rf ~/public_html && ln -s ~/larawebkit/public ~/public_html`) — never put the
   whole project inside `public_html`.
3. In cPanel → **Select PHP Version** (or MultiPHP Manager) choose PHP 8.3 or 8.4 and enable the
   extensions `gd` (or `imagick`), `intl`, `fileinfo`, `mbstring`, `pdo_mysql`, `zip`, `bcmath`, `exif`.

## 3. Configure `.env`

Copy `.env.example` to `.env` and set at least:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://example.com
DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=cpaneluser_larawebkit
DB_USERNAME=cpaneluser_larawebkit
DB_PASSWORD=…
SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
MAIL_MAILER=smtp   # cPanel mail or your provider
```

Optional keys (features stay hidden without them): `PEXELS_API_KEY`, `ANTHROPIC_API_KEY`
(or `OPENAI_API_KEY` / `DEEPSEEK_API_KEY`), `INDEXNOW_KEY`.

## 4. Install

Via cPanel → **Terminal** (or SSH):

```bash
cd ~/larawebkit
php artisan key:generate
php artisan larawebkit:install      # migrations, roles, default pages, first super admin
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

If `storage:link` is not allowed (symlinks disabled), set `MEDIA_DISK=public_uploads` in `.env`:
image variants are then written to `public/uploads` directly.

## 5. Cron (required)

cPanel → **Cron Jobs**, every minute:

```
* * * * * cd ~/larawebkit && php artisan schedule:run >> /dev/null 2>&1
```

The scheduler then:

- writes a heartbeat (checked by `larawebkit:deploy-check`),
- drains the database queue with `queue:work --stop-when-empty` (AI drafts, e-mails),
- announces scheduled posts (cache bust, sitemap, llms.txt, IndexNow),
- prunes the access log according to the retention setting.

Use the full PHP path if `php` is not the right version, e.g. `/usr/local/bin/ea-php83`.

## 6. Check

```bash
php artisan larawebkit:deploy-check
```

It checks PHP and extensions, WebP support, writable directories, `.env` values, the storage link,
the database, pending migrations, the queue driver and the cron heartbeat. It exits with code 1
when a required check fails.

## Updating

```bash
php artisan down
# upload new files (or git pull), then:
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan filament:assets
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan up
```

After changing image sizes in `config/larawebkit-media.php` run `php artisan larawebkit:media:regenerate`.

## Caching and compression

- `public/.htaccess` enables Brotli/gzip and one-year cache headers for build files and image
  variants (their URLs change when they change).
- The full-page cache for anonymous visitors uses the `file` cache store and is busted on every
  content or settings change. Disable it in **Settings → Features** if needed.
- Laravel's default `.htaccess` removes trailing slashes. If you choose the "add trailing slash"
  policy in **Settings → SEO**, remove the "Redirect Trailing Slashes" block from `public/.htaccess`.

## Web application firewalls and AI crawlers

Many shared hosts run ModSecurity or Imunify360, which sometimes block legitimate crawlers
(Googlebot, Bingbot, OAI-SearchBot, ClaudeBot, PerplexityBot …) as "bad bots".
Your choice in **Settings → AI search (GEO)** only works if the requests reach the site:

1. Check **Logs → AI bots & referrals** in the admin: if a bot you allowed never appears, it is
   probably blocked before PHP.
2. In cPanel → **ModSecurity** (or Imunify360 → Firewall) look for blocked requests with those user agents.
3. Ask your host to whitelist the official crawlers. Verify them by reverse DNS or by the IP ranges
   the operators publish (OpenAI, Anthropic, Perplexity, Google and Microsoft all publish them);
   never whitelist by user agent alone, as it is trivial to fake.
4. Cloudflare users: **Security → Bots** — keep "Verified bots" allowed, and check
   "Block AI bots" / "AI Scrapers and Crawlers" matches your robots.txt choice.
