# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project uses [Semantic Versioning](https://semver.org/).

## [0.1.0] - unreleased

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
