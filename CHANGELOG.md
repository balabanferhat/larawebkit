# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project uses [Semantic Versioning](https://semver.org/).

## [Unreleased]

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

### Changed
- Themes moved from `resources/views/themes` and `resources/css/themes` to `themes/{name}`; Vite now only builds
  the optional project bundle `resources/css/app.css`.
- The logo setting moved from Settings → General to Appearance (migrated automatically); the theme is chosen in
  Appearance → Themes. Menus are under Appearance in the admin.

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

[Unreleased]: https://github.com/balabanferhat/larawebkit/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/balabanferhat/larawebkit/releases/tag/v0.1.0
