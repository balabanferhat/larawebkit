# larawebkit — v0.2 plan: "a normal website, done right"

v0.1 delivered the engine (admin, content, editor scoring, media, logs, SEO/GEO). v0.2 makes larawebkit a real
replacement for a WordPress site: modern-looking themes, pages built from sections, and the features most sites need.
Work in the order below and open one PR per part (A, B, C) against `main` from branches `v0.2-a`, `v0.2-b`, `v0.2-c`.
Part A is reviewed visually (screenshots) before B starts.

Same rules as `CLAUDE.md`: tests green (also at `memory_limit=128M`), en + tr, every module droppable, optional
services degrade gracefully, docs + recipe where relevant, CHANGELOG entry, Lighthouse measured not assumed.

## A. Design and page building

1. **Design settings (design tokens).** Admin page "Appearance": logo (light/dark), favicon + generated sizes and
   web manifest, primary/accent colour, font pair (a short list of self-hosted variable fonts, no Google Fonts call
   at runtime), radius scale, default colour scheme (light/dark/system). Themes read only CSS custom properties
   generated from these settings, so a brand change needs no rebuild.
2. **Section blocks for pages** (Filament Builder field, stored as JSON, rendered by Blade components):
   hero (with image/video/background variants), feature grid, call to action, testimonials, FAQ (feeds FAQPage
   schema), pricing table, team, logo cloud, stats, image gallery (WebP pipeline + lightbox), video embed (privacy
   mode, click-to-load), rich text, newsletter form, contact/any form (from B7), latest posts, spacer/divider.
   Each block: a few layout variants, background options (plain/muted/brand/image), anchor id, hide on mobile.
   Blocks are reusable across themes and listed in a recipe ("add a block").
   Page templates become presets of blocks (e.g. "Landing page", "About", "Services").
2b. **Self-contained theme folders (WordPress-like).** Each theme lives in one folder `themes/{name}/`:
   `theme.json` (name, description, version, author, `parent`, supported blocks), `screenshot.webp`, `views/`,
   prebuilt `assets/` (compiled CSS/JS committed, so shared hosting needs no Node), `lang/`. Themes are discovered
   automatically (no hard-coded list in `ThemeManager`); child themes fall back through the `parent` chain to the
   base theme. Admin "Appearance → Themes": cards with screenshot, description, version and "Activate". Uploading a
   theme as a zip is off by default (Blade templates are PHP code); when enabled, super-admin only. Existing
   `minimal`, `business`, `docs` and the new themes below use this structure; `docs/recipes/new-theme.md` updated.
3. **Three distinct modern themes**, each with its own layout, typography and components (not just colour swaps),
   sharing the block library:
   - **Business** — services/agency/company site: strong hero, services, case studies, team, contact.
   - **Personal brand / consultant** — one person's site: portrait hero, about/story, offers (consultation, e-book,
     events), testimonials, newsletter-first.
   - **Magazine / blog** — editorial: featured grid, categories, reading-focused article layout, author pages.
   Keep `docs` as a fourth, documentation-style theme. Each theme ships a **demo content seeder**
   (`php artisan larawebkit:demo {theme}`) using placeholder text and generated/placeholder images (no copyrighted
   content), and committed screenshots (desktop + mobile, light + dark) in `docs/screenshots/themes/`.
4. **Header and footer.** Nested menus (admin UI for `parent_id`), dropdowns with keyboard and touch support,
   accessible mobile menu, optional CTA button, social icons from settings, footer columns (menus + text + contact),
   optional announcement bar.
5. **Designed system pages** in every theme: 404 (with search + popular links), 500, maintenance mode, search
   results, empty states.
6. **Admin look.** Brand the Filament panel from the same settings (logo, primary colour, favicon), Turkish
   validation messages (`lang/tr/validation.php`, `auth.php`, `passwords.php`, `pagination.php`), a cleaner dashboard
   layout, global search over content.

Done when: a fresh install + `larawebkit:demo business` (and each other theme) produces a site that looks like a
modern commercial theme on desktop and mobile, Lighthouse ≥ 95 performance/accessibility/SEO/best-practices on the
demo home and a post page for every theme (report committed in `docs/lighthouse/`), screenshots committed.

## B. Site features

7. **Form builder.** Admin-defined forms (text, email, phone, textarea, select, checkbox, radio, date, file with
   type/size limits, consent checkbox with versioned text), honeypot + rate limit, submissions list with status and
   notes, email notification (reply-to the sender), CSV export, optional confirmation email, embeddable as a block.
   The v0.1 contact form becomes a seeded form.
8. **Front-end members** (separate guard/table from admins): register, login, email verification, password reset,
   profile, delete account (GDPR/KVKK), members-only content (full text never leaks into meta, JSON-LD, feeds,
   `.md` twins or `llms-full.txt`), admin list of members.
9. **Newsletter sending** on top of the v0.1 double opt-in: compose a campaign in the admin (editor + preview +
   test send), send to confirmed subscribers through the database queue in small batches (shared-hosting friendly,
   configurable rate), unsubscribe link + `List-Unsubscribe`/`List-Unsubscribe-Post`, send log, basic stats.
   **Lead magnet:** attach a downloadable file to a form/newsletter signup; signed, expiring download link after
   confirmation.
10. **Events (optional module).** Event with date/time/timezone, duration, capacity, online link (e.g. Zoom) or
    location, free registration (member or guest), confirmation email with calendar file (.ics), online link shown
    only to registrants (signed link for guests), admin "send link to registrants", registrations CSV, Event schema.
11. **WordPress import.** `php artisan larawebkit:import:wordpress {site-url|export.json} [--dry-run]` via the WP
    REST API: posts, pages, categories, tags, authors, featured + inline images through the WebP pipeline, clean
    block markup, preserve URLs (keep the same paths or create 301s), idempotent re-runs, skip list by category.
    Tests use recorded fixtures only.
12. **Article extras.** Share buttons (no third-party scripts), reading time, table of contents for long posts,
    previous/next and related posts in every theme.

## C. Operations

13. **Backups.** `larawebkit:backup` (database dump + uploads, zip, retention), download from admin, optional
    upload to FTP/SFTP or S3-compatible storage, scheduled via the existing cron; restore documented.
14. **Admin conveniences.** SMTP settings test ("send test email"), shareable draft preview link (signed,
    expiring), duplicate page/post, bulk actions (publish, unpublish, move category, delete), scheduled list view.
15. **Measurement.** Lighthouse CI (or a documented script) run against the demo sites; budgets enforced in CI if
    practical.

## Not in v0.2 (v0.3+)
Payments (Stripe, iyzico, PayTR), comments, multi-site, visual drag-and-drop page editor, extracting modules into
Composer packages so existing projects can receive updates (keep modules decoupled now so this stays possible).
