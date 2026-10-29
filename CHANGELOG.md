# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project uses [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added (v0.3 preparation)
- **Magazine theme, v0.3:** the front page, the blog / category / tag archives, search and the 404 page are built
  from block presets (`magazine`, `archive`, `not_found`) instead of fixed markup. The `latest_posts` block gets a
  `front` variant (lead story, two secondary stories, a latest rail) and an `offset` field. "Magazine front page"
  is offered as a page preset, so editors can make their own front page from the same blocks. Archives show
  the newest story large on the first page. Screenshots of every page (desktop / mobile, light / dark) are in
  `docs/screenshots/magazine/`. Lighthouse: home 97, article 98, all other categories 100.
- Demo sites say so: `larawebkit:demo` sets an announcement that all names, articles and images are made-up
  examples.
- `docs/child-themes.md`: building a child theme step by step. `docs/security-review.md`: the security review
  and its findings.
- Tests for the magazine theme, and a contrast test (4.5:1 for text colours on every background, in every
  theme and colour scheme).

### Fixed (v0.3 preparation)
- **Accessibility** (axe, WCAG 2.2 AA, 8 pages × desktop / mobile × light / dark, now without violations):
  - link, muted and accent text colours are adjusted against every background they sit on, not only the soft
    one; the consent banner link in dark mode was 4.23:1;
  - the announcement bar and the magazine top bar are landmarks;
  - the big 404 number has enough contrast.
- **Security** (details in `docs/security-review.md`):
  - array input (`?q[]=x`, a crafted anti-spam token, `?email[]=x` on the member reset page) no longer causes
    a server error;
  - `%` and `_` in a search are matched literally instead of listing every post;
  - uploaded form files get the extension of their content.

Feedback from migrating a Turkish personal-brand WordPress site onto 0.2.0 without touching the core.

### Added
- **Newsletter consent** (KVKK / GDPR): Settings → Newsletter → "Ask for explicit consent" adds an unticked
  checkbox with your text and a privacy link; the accepted text, its version and the time are stored per
  subscriber, shown in the admin and in the new subscriber CSV export.
- Newsletter sign-up box title and text in the settings (`newsletter.band_title`, `newsletter.band_text`);
  the personal and magazine themes no longer promise a sending schedule.
- **Post URL prefix**: `POST_PREFIX` (default `blog`; empty = `/{slug}` like WordPress post-name permalinks,
  a page with the same path wins) and `BLOG_PATH` for the listing and the category / tag archives.
- **Events**: the path per language (`EVENTS_PATH`, `EVENTS_PATHS=tr:etkinlikler`) and an "Upcoming events"
  section block.
- **Classic font pair**: system serif headings and system sans body, nothing downloaded.
- Newsletter consent can link to a page of your choice (`newsletter.consent_page`, e.g. a separate KVKK notice;
  its translation is used per language); empty keeps the privacy policy.
- `Category::publicPath()` and `Tag::publicPath()` take an optional locale, like `blog_path()` and `post_path()`.
- **WordPress import**: `--gone` answers the old URLs of skipped posts and categories with 410 Gone (an admin
  change to 301 survives re-runs); empty theme-part pages (slider, header...) are skipped and their old address
  sent home (`--keep-empty-pages` to import them); featured images without alt text get the post title (or a
  meaningful media title) and every image without alt text is listed in the report.

### Fixed
- WordPress import built redirect targets with the console locale (`/en/blog/...`) instead of `--locale`.
- Automatic WordPress excerpts glued paragraphs together ("Nerede?Dışarıda"); they are rebuilt from the body,
  hand-written excerpts are kept. `MarkupCleaner::text()` keeps a space between blocks.
- An old URL with a trailing slash that has an admin redirect is answered in one hop instead of two
  (trailing-slash 301, then the redirect).
- The privacy and cookie policy stubs are created in every active language and linked as translations;
  seeding before choosing a site language warns.
- WordPress import: an empty page whose address is a route here (an empty "Blog" page at `/blog/`, the events
  path...) is skipped without a redirect, so it no longer hides the post listing; such a redirect left by an
  earlier import is removed.
- WordPress import: re-runs keep the importer's own (automatic) redirects pointing at the posts' current
  addresses, also for unchanged posts, so changing `POST_PREFIX` after an import works; a redirect whose old
  address is now the post's own address is deleted. Hand-made redirects are never touched, and a redirect
  edited in the admin counts as hand-made.
- Console commands build URLs in the site language (`/blog`, not `/en/blog` on a Turkish site).
- WordPress import: posts with an empty excerpt get one from the body paragraphs, like automatic excerpts.

## [0.2.0] - 2026-10-29

### Added
- **Theme system** (WordPress-style): every folder in `themes/` with a `theme.json` (name, description, version,
  author, parent, supported blocks, design defaults) is discovered automatically; views, assets, translations and
  defaults resolve through the parent chain, with `minimal` as the root. Prebuilt CSS/JS are committed
  (`larawebkit:themes:build` bundles `src/theme.css` in PHP, `--check` for CI), copied to `public/themes` on
  activation or by `larawebkit:themes:publish`, and served by a PHP route until then. **Appearance → Themes** shows
  screenshot cards with Activate. Zip upload is off by default (`THEMES_ALLOW_UPLOAD`) and super-admin only, with
  path, type and size checks.
- **Appearance**: logo (light/dark), site icon (favicon.ico, PNG, Apple touch icon, web-app icons and
  `/site.webmanifest`), brand and accent colours (link/button colours adjusted to WCAG AA in light and dark mode),
  six self-hosted variable font pairs (OFL, latin + latin-ext, no Google Fonts request), corner radius and default
  colour scheme. Themes read CSS custom properties, so a brand change needs no rebuild.
- **Section blocks** for pages (new `blocks` module): hero, features, CTA, testimonials, FAQ (FAQPage schema),
  pricing, team, logos, stats, gallery with lightbox, video (youtube-nocookie / Vimeo dnt, click-to-load), rich
  text, newsletter, form, latest posts, spacer; variants, backgrounds, spacing, anchors and hide-on-mobile;
  page presets (landing, about, services, contact). Block text feeds the Markdown twin, word count and scores;
  block images count as media usage. Recipe: `docs/recipes/new-block.md`.
- **Four themes**: Business, Personal brand, Magazine and Docs, each with its own layout and typography, on a
  shared design system in `themes/minimal`. Lighthouse 98–100 on the home page and a post of every demo
  (`docs/lighthouse/`); screenshots in `docs/screenshots/themes/`.
- **Demo content**: `php artisan larawebkit:demo {theme}` (themes/{name}/demo.php) with placeholder text in the
  site language and images generated locally; re-runs replace the previous demo, `--remove` deletes it, the
  site's own pages are never changed.
- **Header and footer**: nested menus (dropdown items) with accessible dropdowns (hover/focus without JS,
  toggle buttons, Escape) and a mobile menu, header CTA button, dismissible announcement bar, footer columns
  (two menus, about text, contact details, social icons), legal links row.
- **System pages**: 404 with search and popular pages, self-contained 500 / 503 (maintenance) / 429 / 419 / 403
  pages that render without the database, empty states.
- **Admin**: the panel uses the site's logo, favicon and primary colour; Turkish validation, auth, password and
  pagination messages; global search (Cmd/Ctrl+K) over pages, posts, media and users; dashboard with a welcome
  widget, quick actions and a set-up checklist.
- CI runs the suite at 128M as well (one process per test file) and checks that prebuilt theme CSS is current.
- **Form builder** (new `forms` module, replaces the v0.1 contact form): text, e-mail, phone, textarea, select,
  radio, checkbox, date, file and consent fields (consent text and version stored with each submission), half
  or full width, notification e-mails to several addresses with reply-to, optional confirmation to the sender,
  submissions with status and notes, CSV export (UTF-8 BOM, formula-safe), honeypot and rate limit; the `form`
  block embeds any form. v0.1 contact messages are moved into the seeded contact form.
- **Lead magnet**: a form or the newsletter confirmation can hand out a file from the private disk through a
  signed link that expires after `DOWNLOAD_LINK_HOURS` (core `SignedDownload`).
- **Front-end members** (optional `members` module, Settings → Features): registration with e-mail
  verification, login with throttling, password reset, profile and e-mail change, account deletion that other
  modules can hook into (`MemberDeleting`), members-only posts whose text never appears in meta, JSON-LD,
  feeds, `.md` twins, `llms-full.txt` or search; admin list of members. Separate `member` guard: members never
  reach the admin.
- **Newsletter campaigns**: compose with the editor, preview, test send, send now or schedule; the scheduler
  sends `NEWSLETTER_BATCH_SIZE` e-mails a minute through the database queue; `List-Unsubscribe` and RFC 8058
  one-click unsubscribe; send log, sent / failed / unsubscribed counts, cancel.
- **Events** (optional `events` module): date, time zone, duration, capacity, online / in person / hybrid,
  free registration for members (one click) and guests, confirmation e-mail with an `.ics` file, the online
  link encrypted at rest and shown only on the registrant's signed page, cancel registration, admin "send link
  to registrants", registrations CSV, Event schema, `/events` listing and sitemap.
- **WordPress import**: `php artisan larawebkit:import:wordpress {site-url|export.json} [--dry-run]` imports
  authors, categories, tags, pages and posts with featured and inline images through the WebP pipeline,
  cleans block markup, keeps page paths and adds 301s for changed URLs, skips categories on request and is
  idempotent (`import_map`). Guide: `docs/import-wordpress.md`.
- **Article extras** in every theme: share links (plain URLs, no third-party scripts; "copy link" with
  JavaScript), previous / next article, related posts filled up with the latest ones.
- Modules can add their own sitemap with `SitemapController::extend()`.
- **Backups** (new `backup` module): `php artisan larawebkit:backup` puts the database (a SQLite copy, or a
  SQL dump written in PHP for MySQL / MariaDB, no `mysqldump` needed) and the uploaded files into one zip,
  optionally AES-encrypted (`BACKUP_PASSWORD`); daily or weekly through the cron (Settings → Backups), keeps
  the newest N here and on an optional FTP / SFTP / S3-compatible disk (`BACKUP_REMOTE_DISK`); System →
  Backups lists, downloads, deletes and starts backups (super-admins only); `deploy-check` warns about stale
  backups. Restore guide: `docs/backups.md`. CI tests the SQL dump on MariaDB.
- **Admin conveniences**: "Send test e-mail" on the settings page (shows the SMTP error right away), "Share
  preview link" for drafts (signed, expires after `PREVIEW_LINK_HOURS`, noindex), duplicate a page or post as
  a draft, bulk publish (respecting the publish gate), move to drafts and set category, and All / Published /
  Scheduled / Drafts / Pending tabs on the page and post lists.
- **Lighthouse budgets in CI**: `scripts/lighthouse/run.mjs` measures every theme's demo against
  `scripts/lighthouse/budgets.json` (scores and transfer sizes); the CI job uploads the reports.

### Changed
- Themes moved from `resources/views/themes` and `resources/css/themes` to `themes/{name}`; Vite now only builds
  the optional project bundle `resources/css/app.css`.
- The logo setting moved from Settings → General to Appearance (migrated automatically); the theme is chosen in
  Appearance → Themes. Menus are under Appearance in the admin.
- The contact form is now a form of the form builder; the "Contact form" feature toggle and the
  `contact_messages` table are gone (messages are migrated). The permission `manage contact messages` became
  `manage forms`; a migration renames it and adds the new permissions to the built-in roles.

## [0.1.0] - 2026-10-29

### Added
- Laravel 13 + Filament 5 + Pest 4 foundation, module system (`config/larawebkit.php`), `composer setup`,
  `larawebkit:install`, `larawebkit:deploy-check`, scheduler heartbeat and queue draining for shared hosting.
- Typed settings with a generated admin page.
- Admin users, roles (super-admin, editor, author) and permissions, TOTP 2FA, password reset, sessions page,
  security headers, honeypot and rate limiting.
- Audit log with per-record history, auth log, cookieless access log with retention, 404 log, AI bot report.
- Pages, posts, categories, tags, redirects, menus, revisions with diff/restore, scheduled publishing, translations.
- Media library with an all-WebP image pipeline, usage tracking and `larawebkit:media:regenerate`.
- Content editor: media modal, live SEO / human / GEO scores (PHP + JS, shared fixtures), publish gate,
  autosave, Pexels integration with attribution, AI assistant.
- AI layer: Anthropic, OpenAI, DeepSeek, Ollama drivers, prompt files, usage log, monthly budget.
- Minimal, business and documentation themes with dark mode; contact form; double opt-in newsletter.
- SEO (meta, Open Graph, JSON-LD, sitemaps, robots.txt, RSS, IndexNow, canonical host, page cache) and
  GEO (llms.txt, llms-full.txt, Markdown twins, AI crawler policy).
- Cookie consent, policy page templates, admin dashboard.
- Docs: architecture, shared-hosting deployment, recipes; CI on PHP 8.3/8.4 with Pint.

### Fixed
- Image pipeline memory: input megapixel cap and memory estimate before decoding (clear refusal instead of a
  fatal error), a single full-size bitmap per upload, EXIF rotation after the first downscale, OG image from the
  largest variant. Test suite passes at `memory_limit=256M` (enforced in `phpunit.xml` and CI, including Windows).
- `larawebkit:deploy-check` warns when `memory_limit` is below 256M; its test no longer depends on `public/storage`.

[Unreleased]: https://github.com/balabanferhat/larawebkit/compare/v0.2.0...HEAD
[0.2.0]: https://github.com/balabanferhat/larawebkit/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/balabanferhat/larawebkit/releases/tag/v0.1.0
