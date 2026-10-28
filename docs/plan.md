# larawebkit — first release plan (v0.1)

Goal: `git clone` → `composer setup` → a working site with admin, content, editor, logs and SEO in minutes,
and a codebase an AI agent can extend safely. Projects built on it should start at their own business logic, not at login screens.

## 1. Foundation
- Laravel 13, Filament 5, Pest, Tailwind/Vite. `composer setup` script: install, `.env` from example, key, migrate, seed, storage link, build assets.
- `php artisan larawebkit:install` interactive: site name, locale (en/tr), first admin user.
- Settings module: typed key/value settings editable in admin (site name, logo, contact email, social links, analytics IDs, feature toggles); cached; secrets stay in `.env`.

## 2. Admins, roles, security
- Admin users separate from (optional) front-end members. Roles and permissions (spatie/laravel-permission or equivalent), seeded roles: super-admin, editor, author.
- 2FA (TOTP) for admins, password reset, login throttling, session list with "log out other devices".
- Security headers middleware, CSRF everywhere, honeypot + rate limit on public forms.

## 3. Logs
- **Audit log:** who changed what (model, id, old/new values, user, IP, user agent, time) for every admin-managed model; viewable and filterable in admin; per-record history tab.
- **Access log:** front-end requests (path, status, referrer, user agent class, duration, day); IP stored hashed or truncated; retention setting + prune command.
- **Auth log:** successful/failed logins, 2FA events, password resets.

## 4. Content
- Pages (hierarchical, custom slug, template choice) and Posts (categories, tags, author, published_at, scheduled publishing, featured image, excerpt, members-only flag).
- Media library (uploads, alt text required, usage tracking). **Image pipeline, all images WebP:**
  - every upload (and every Pexels / imported image) is converted to WebP and resized into variants (e.g. 320, 640, 960, 1280, 1920 px wide, never upscaled); original kept privately for re-processing; EXIF/GPS stripped; quality configurable
  - works with GD or Imagick (whichever is available on shared hosting); JPEG/PNG fallback only if the server cannot write WebP
  - front end always renders `<img>` with `srcset`/`sizes`, explicit `width`/`height` (no layout shift), `loading="lazy"` + `decoding="async"` except the LCP/hero image which gets `fetchpriority="high"` and is preloaded
  - `php artisan larawebkit:media:regenerate` rebuilds variants after a config change
- Redirects (301/302/410) managed in admin; automatic redirect when a slug changes.
- Menus (header/footer) editable in admin.
- Revisions: every save stores a version; diff view and "restore this version".

## 5. Content editor module (core feature)
Rich editor for pages and posts with live scoring panels. Specification:
- Editor: Quill 2 (or TipTap if it fits Filament better): H2/H3, bold/italic/underline, ordered/bulleted lists, blockquote, link, image, clean. Server-side HTML sanitizing of editor output.
- **WordPress-style media modal** inside the editor (used for inline images and the featured image):
  - tabs: "Media library" and "Upload"; later tab "Pexels" (below) in the same modal
  - library: search box, responsive thumbnail grid (square crops), "load more" pagination, newest first, selected state
  - upload: drag-and-drop zone + file picker, multiple files, progress and error messages, max size/type validation
  - details sidebar for the selected item: preview, file name, title, alt text (editable, required before insert), caption,
    dimensions (px), file size, upload date, "used in N posts/pages", copy URL, delete (only with permission, and warns if in use)
  - actions: "Insert into content" (with size choice: thumbnail / medium / large / full, and alignment) and "Set as featured image"
- Keyword chips, meta description field with live counter, slug preview, Google-style snippet preview.
- **Autosave** draft every N seconds + "unsaved changes" warning.
- **SEO score (0-100)**, computed identically in the browser (live) and on the server (authoritative), weights configurable in `config/larawebkit-editor.php`:
  - word count: ≥800 → 20, ≥500 → 12, else 5
  - a keyword appears in the title → 15
  - meta description 120-160 chars → 15; present but out of range → 8
  - H2 count: ≥4 → 15, 2-3 → 10
  - keyword density 1-3% → 15; present but out of range → 8
  - uses a list → 5; uses bold → 5
  - (new) image with alt text present, at least one internal link: add as configurable checks
  - Each check shown as pass/fail with a hint.
- **Human score (0-100)**, local heuristics only (no external API), starts at 100:
  - sentence-length coefficient of variation < 0.35 → −25, < 0.5 → −10
  - average sentence > 32 words → −10
  - each cliché from the locale's list → −5 (max −35); lists in `config/larawebkit-editor/cliches/{locale}.php` (ship en + tr)
  - each group of ≥3 sentences starting with the same word → −5 (max −15)
  - contains a question → +3; addresses the reader ("you"/"siz") → +4 (locale words configurable)
- **Publish gate** (configurable, can be disabled): SEO ≥ 60, words ≥ 300, human ≥ 45; editors with permission can override with a logged reason.
- **Pexels stock photos** (optional, `PEXELS_API_KEY`):
  - search modal inside the editor (query box, landscape/portrait filter, paginated results) to pick a featured or inline image
  - "suggest image" button: AI (if configured) turns the title into 3-5 English search keywords, else uses the title
  - downloads the chosen photo, stores it in the media library resized (e.g. 1600 + 800 variants, WebP), avoids reusing the same photo on the same post
  - **stores and displays attribution** ("Photo by {photographer} on Pexels" with links), as Pexels guidelines require
- **AI writing assistant** (optional): generate draft from a title/brief, improve/rewrite selection, suggest tags, suggest meta description, suggest topics. Runs through the AI layer below; long jobs go to the queue with status polling.

## 6. AI layer
- `AiManager` with drivers: Anthropic (Claude, default), OpenAI, DeepSeek, OpenAI-compatible local (Ollama). Configured in `config/larawebkit-ai.php` + `.env`; order/fallback list; per-action model choice (cheap model for tags/keywords, strong model for drafts).
- Prompts live in versioned files (`resources/prompts/*.md`), locale-aware, overridable by the project.
- Usage log: action, provider, model, input/output tokens, estimated cost, user; monthly budget limit setting that disables AI actions when reached.
- Uses the official SDK for each provider where one exists; no secrets in prompts or logs.

## 7. Front end and templates
- 2-3 starter themes (minimal blog, business/landing, documentation) selectable in settings; Blade components; dark mode; accessible (WCAG AA contrast, keyboard nav).
- Email templates (Markdown mailables) with site branding.
- Contact form (stored + emailed), newsletter signup with double opt-in (optional module).

## 8. SEO (fully optimized), GEO, privacy
- `sitemap.xml` index + per-type sitemaps with `lastmod` and image entries (auto), `robots.txt` (editable), `llms.txt` (auto from pages/posts), RSS feed.
- Per-page title/description/canonical/OG/Twitter tags with sensible fallbacks; OG image auto-generated 1200×630 WebP/JPEG from the featured image; `hreflang` when more than one locale is active.
- JSON-LD: Organization or Person, WebSite (+ SearchAction), BlogPosting/Article (author, dates, image), BreadcrumbList, FAQPage when the content has a FAQ block.
- Clean URLs, one canonical host (www / non-www and https redirect setting), trailing-slash policy, automatic 301 on slug change, 404 log in admin with "create redirect" button.
- Noindex controls per page; tag/search/pagination pages noindex by default; no thin auto-generated pages.
- Semantic HTML (one H1, landmarks, breadcrumbs), internal "related posts" links, table of contents for long posts.
- Performance as SEO: target Lighthouse ≥ 95 on the starter themes — minimal CSS/JS, no render-blocking third-party scripts, full-page cache for anonymous visitors (file cache, works on shared hosting) with cache bust on publish, gzip/brotli + long cache headers for assets via `.htaccess`.
- IndexNow ping on publish/update (optional key), Search Console / Bing verification meta tags in settings.

### GEO (visibility in AI search: ChatGPT, Claude, Perplexity, Gemini, Google AI Overviews)
- `llms.txt` (site summary + curated links per section, editable intro in admin) and `llms-full.txt` (full text of published, indexable content), both regenerated on publish and cached.
- Markdown twin of every page/post at `{url}.md` (and `Accept: text/markdown`), linked from `llms.txt`, so AI agents can read clean text.
- AI crawler policy in admin: per-bot allow/deny (GPTBot, OAI-SearchBot, ChatGPT-User, ClaudeBot, Claude-SearchBot, PerplexityBot, Google-Extended, Applebot-Extended, CCBot, Bytespider...) written into `robots.txt`; separate "allow AI search" vs "allow AI training" presets. Shared-hosting WAF note: document how to whitelist good bots.
- Citability built into the editor and templates: optional TL;DR / key-takeaways block at the top, FAQ block (→ FAQPage schema), "last updated" date shown and in schema, sources/references block, author box.
- Entity / E-E-A-T: author profile pages (bio, expertise, photo, `sameAs` social links) → Person schema; Organization settings (logo, contact, `sameAs`) → Organization schema on every page.
- Editor adds a **GEO check** next to SEO and human scores: has summary, has question-style H2s, has FAQ or lists/tables, has sources, has author, answer-first paragraph length; each as pass/fail with hints (weights configurable).
- Admin report: AI bot hits from the access log (which bots crawl which pages, last seen), and referrals from AI assistants (chatgpt.com, perplexity.ai, claude.ai, gemini.google.com, copilot) in the analytics dashboard.
- Cookie consent banner (necessary / analytics / marketing); no tracking cookie before consent; built-in cookieless page counter from the access log feeding an admin dashboard (top pages, referrers, daily views).
- Privacy policy and cookie policy page stubs (clearly marked as templates to be reviewed).

## 9. Admin dashboard
- Widgets: views last 30 days, top pages, recent audit events, failed logins, AI usage and budget, content needing attention (low SEO/human score, drafts, scheduled).

## 10. Deployment
- `docs/deploy-shared-hosting.md`: cPanel steps (document root → `public/`, PHP version, `storage:link`, cron `schedule:run` every minute, queue via `queue:work --stop-when-empty` from the scheduler).
- `php artisan larawebkit:deploy-check`: checks PHP extensions, writable dirs, `.env` values, cron heartbeat.
- GitHub Actions CI: tests on PHP 8.3/8.4, Pint style check.

## 11. AI-ready repo
- `CLAUDE.md` + `AGENTS.md` (same rules, for other agents).
- `docs/recipes/`: new content type, new Filament resource, new setting, new AI action, new theme, new locale, new audit-logged model — each with the files to touch and the tests to add.
- `docs/architecture.md`: module map and how they connect.

## 12. Repo hygiene
- README (what it is, screenshots placeholder, quick start, feature list, roadmap), MIT LICENSE, CONTRIBUTING, SECURITY.md, CHANGELOG, issue templates.

## Done for v0.1 when
- Fresh clone → `composer setup` → `php artisan serve` works; `php artisan test` green in CI.
- Admin can log in with 2FA, create a page and a post in the editor, see live SEO + human scores, pick a Pexels photo (with key) with attribution, see the change in the audit log, and see the post on the front end with correct SEO tags in sitemap and llms.txt.
- Without any third-party keys everything still works; optional features are hidden.

## Later (v0.2+)
- Front-end members + members-only content, paid plans (payment driver interface: Stripe, iyzico, PayTR), events/booking, multi-site, import from WordPress (REST API), page builder blocks.
