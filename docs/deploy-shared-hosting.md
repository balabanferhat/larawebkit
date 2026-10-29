# Deploying to shared hosting (cPanel)

larawebkit runs on plain shared hosting: PHP 8.3+, MySQL/MariaDB, cron. No Redis, no Node, no
long-running worker in production.

## 1. Build locally (or in CI)

Production servers do not need Node. Theme CSS and JS are prebuilt and committed in `themes/{name}/assets`, so
a plain upload works. Only if your project adds Tailwind utilities to its own views (`resources/css/app.css`)
build that bundle on your machine or in CI:

```bash
composer install --no-dev --optimize-autoloader
php artisan filament:assets                # admin assets in public/js, public/css
npm ci --ignore-scripts && npm run build   # optional: public/build for resources/css/app.css
```

## Memory: set `memory_limit` to 256M

Images are decoded into bitmaps of about **4.6 bytes per pixel** before they are resized to WebP.
A 12 MP photo needs ~55 MB, a 24 MP phone photo ~110 MB, on top of ~60-100 MB for Laravel itself.

| `memory_limit` | Largest image that can be processed (approx.) |
|---|---|
| 128M | ~10 MP (many phone photos are refused) |
| **256M (recommended)** | ~40 MP — every common phone and camera photo |
| 512M | ~90 MP |

How larawebkit protects a small server:

- Before decoding, the image header is read and the upload is **refused with a clear message** when it is larger than
  `MEDIA_MAX_MEGAPIXELS` (default 40) or when its estimated bitmap does not fit into the memory that is still free.
  The user sees e.g. "The image is 6000 × 4000 px (24 MP), too large for this server's memory (memory_limit 128M,
  about 10.9 MP fit)" instead of a blank error page.
- Only one full-size bitmap is ever held: the original is downscaled in place to the largest variant, every smaller
  variant is made from the previous one, rotation (EXIF) is applied after the first downscale, and the Open Graph
  image is cut from the largest variant, not the original.
- `php artisan larawebkit:deploy-check` warns when `memory_limit` is below 256M and prints the size that fits.

Raise the limit in cPanel → **Select PHP Version → Options** (or **MultiPHP INI Editor**): `memory_limit = 256M`.
If your host does not allow it, lower `MEDIA_MAX_MEGAPIXELS` (e.g. `10` for 128M) so editors get the size limit
up front, or resize photos before uploading. GD and Imagick need about the same memory; Imagick counts it outside
PHP's limit, so set `MEDIA_MAX_MEGAPIXELS` to protect the server in that case.

## 2. Upload and point the document root

1. Upload the project (everything except `node_modules`, `.env`, `storage/logs/*`) to e.g. `~/larawebkit`.
2. In cPanel → **Domains**, set the document root of the domain to `~/larawebkit/public`.
   If the host does not allow changing it, symlink `public_html` to `larawebkit/public`
   (`rm -rf ~/public_html && ln -s ~/larawebkit/public ~/public_html`) — never put the
   whole project inside `public_html`.
3. In cPanel → **Select PHP Version** (or MultiPHP Manager) choose PHP 8.3 or 8.4 and enable the
   extensions `gd` (or `imagick`), `intl`, `fileinfo`, `mbstring`, `pdo_mysql`, `zip`, `bcmath`, `exif`.
   Enable `exif` even though it is optional: it is how larawebkit reads the EXIF orientation of phone photos.
   Without it, photos taken in portrait (or upside down) may be stored rotated; `larawebkit:deploy-check` warns about it.

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
php artisan larawebkit:themes:publish   # copy theme assets to public/themes (served by the web server)
php artisan larawebkit:demo business    # optional: demo content for a theme (business, personal, magazine, docs)
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
- prunes the access log according to the retention setting,
- sends newsletter campaigns in small batches (one batch of `NEWSLETTER_BATCH_SIZE`, default 50, per minute),
  which keeps you under the hourly sending limits most hosts set; ask your host for the limit and lower the
  batch size if needed,
- backs up the database and uploads every night at 03:00 and keeps the newest seven (Settings → Backups;
  copies to FTP, SFTP or S3 and restore steps in [backups.md](backups.md)).

Members, events and the newsletter are off until you enable them in **Settings → Features**. They send e-mail
(verification, password reset, registration with a calendar file, campaigns), so set `MAIL_*` in `.env` and send
yourself a test first. Signed links in those e-mails use `APP_URL`; set it to the public https address.

Use the full PHP path if `php` is not the right version, e.g. `/usr/local/bin/ea-php83`.

## 6. Check

```bash
php artisan larawebkit:deploy-check
```

It checks PHP and extensions, `memory_limit` (warns below 256M), WebP support, writable directories, `.env` values, the storage link,
the database, pending migrations, the queue driver and the cron heartbeat. It exits with code 1
when a required check fails.

## Updating

```bash
php artisan down
# upload new files (or git pull), then:
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan filament:assets
php artisan larawebkit:themes:publish
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan up
```

After changing image sizes in `config/larawebkit-media.php` run `php artisan larawebkit:media:regenerate`.

## Caching and compression

- `public/.htaccess` enables Brotli/gzip and one-year cache headers for build files, theme assets and image
  variants (their URLs change when they change).
- Theme assets are copied to `public/themes/{name}` on activation and by `larawebkit:themes:publish`. If the
  copy is not possible, a PHP route serves them from `themes/` (slower, but the site keeps working).
- Fonts are self-hosted (`public/fonts/lwk`, SIL Open Font License): no request to Google Fonts.
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
