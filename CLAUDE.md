# larawebkit — AI-ready Laravel starter kit

Open-source (MIT) starter kit for building websites and web apps with an AI coding agent from day one:
pages, posts, a content editor with SEO / human-ness scoring and stock photos, a good admin panel,
access and audit logs, admins and roles, templates, SEO, privacy/consent, and recipes that let an AI
extend the kit consistently. Plan and first-release scope: [docs/plan.md](docs/plan.md).

## Stack
- PHP 8.3+, Laravel 13, Filament 5 (admin), Pest (tests), Blade + Tailwind (Vite) for the front end.
- SQLite locally and in tests; MySQL/MariaDB in production. Must run on plain shared hosting (cPanel): no Redis, no Node in production, queue via `database` driver + cron.

## Rules
- **Public repo.** No secrets, real API keys, customer data, or code copied from private projects. Every secret comes from `.env`; `.env.example` lists every variable with an empty or dummy value.
- Generic only: no domain-specific (medical, legal, ...) logic in core. Domain features belong in optional modules.
- i18n from the start: every UI string through `__()`; ship `en` and `tr`. Language-dependent heuristics (e.g. cliché lists) live in per-locale config files, not code.
- Every feature comes with Pest tests; `php artisan test` must be green before work is considered done.
- Third-party services are optional and degrade gracefully: if `PEXELS_API_KEY` or an AI provider key is missing, the feature is hidden or disabled, never a crash.
- Respect third-party terms: Pexels photos always store and display photographer attribution and a link to the photo.
- Keep modules decoupled: each feature in its own namespace / service provider so a project can drop it.
- Code, comments, commits and docs in English; Turkish lives in `lang/tr`.

## Extending (for AI agents)
Before adding a feature, read `docs/architecture.md` (module map) and `docs/recipes/`, and follow the matching recipe
(new content type, new admin resource, new setting, new AI action, new theme/template, new locale, new audit-logged model,
new module). If no recipe fits, write one alongside the feature.

Useful commands: `php artisan test` (Pest), `npm test` (JS scorer parity), `vendor/bin/pint` (style),
`php artisan larawebkit:deploy-check`. If you change scoring rules, update both
`app/Modules/Editor/Scoring` and `resources/js/larawebkit/scoring.js`, then regenerate
`tests/fixtures/scoring/expected.json` with `php tests/fixtures/scoring/generate.php`.
