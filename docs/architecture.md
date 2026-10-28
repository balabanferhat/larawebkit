# Architecture

larawebkit is a normal Laravel 13 application. Every feature is a **module** in `app/Modules/<Name>`
with its own service provider and, when it has admin screens, a Filament plugin. Modules are
listed in `config/larawebkit.php`; removing a line drops the module (its classes stay, but nothing
is registered).

```
config/larawebkit.php ─► App\Modules\Core\LarawebkitServiceProvider
                           ├─ registers each module's ServiceProvider (routes, events, middleware, commands)
                           └─ AdminPanelProvider ->plugins(ModuleRegistry::adminPlugins())
```

## Module map

| Module | What it does | Key classes |
|---|---|---|
| Core | module registry, `larawebkit:install`, `larawebkit:deploy-check`, scheduler (heartbeat, queue), morph map, `localized_path()` | `ModuleRegistry`, `LarawebkitServiceProvider` |
| Settings | typed key/value settings in the `settings` table, cached; admin page built from `config/larawebkit-settings.php`; `setting()` helper | `SettingsRepository`, `ManageSettings` |
| Security | admin users, roles & permissions (spatie), TOTP 2FA (Filament MFA), sessions page, security headers, honeypot + rate limit | `RolesAndPermissions`, `SecurityHeaders`, `ProtectPublicForm`, `Login` |
| Logs | audit log (`Auditable` trait), auth log (event subscriber), cookieless access log (middleware), prune command, AI bot report | `AuditLogger`, `LogAccess`, `AgentClassifier` |
| Media | uploads → WebP width variants + square thumb, private originals, usage tracking, regenerate command, JSON API for the editor modal | `ImagePipeline`, `ResponsiveImage`, `ContentImages`, `TracksMediaUsage` |
| Content | pages (hierarchical), posts, categories, tags, redirects, menus, revisions, front controllers, locale prefixes, scheduled publishing | `HasContent`, `HasRevisions`, `HtmlSanitizer`, `ContentController` |
| Editor | the editor form (Filament RichEditor/TipTap + media modal), SEO/human/GEO scorers (PHP + identical JS), publish gate, autosave, Pexels | `EditorSchema`, `ContentScorer`, `PublishGate`, `PexelsClient` |
| Ai | `AiManager` with Anthropic / OpenAI / DeepSeek / Ollama drivers, fallback order, per-action model tier, prompt files, usage log, monthly budget, queued tasks | `AiManager`, `PromptRepository`, `RunAiTask` |
| Themes | theme resolution with fallback to `minimal`, content renderer (TOC, heading anchors), theme CSS via Vite with inline fallback | `ThemeManager`, `ContentRenderer`, `ThemeAssets` |
| Seo | `SeoData` (meta, OG/Twitter, hreflang, JSON-LD), sitemaps, robots.txt, RSS, IndexNow, canonical host, full-page cache, 404 log | `SeoData`, `CanonicalUrl`, `PageCache`, `OgImage` |
| Geo | `llms.txt`, `llms-full.txt`, Markdown twins, AI crawler policy | `LlmsTxt`, `MarkdownTwin`, `AiCrawlerPolicy` |
| Privacy | cookie consent banner (no analytics before consent), policy page stubs | `components/lwk/consent.blade.php` |
| Contact / Newsletter | contact form (stored + e-mailed), double opt-in newsletter (optional) | `ContactController`, `NewsletterController` |
| Dashboard | admin widgets: views, top pages, referrers (AI highlighted), content needing attention, recent changes, AI budget | `Analytics`, `Widgets\*` |

## How the modules talk

Modules communicate through **events and small contracts**, not direct calls:

- `Content\Events\ContentSaved` / `ContentPublished` → Seo busts the page cache and sitemaps and
  pings IndexNow; Geo flushes `llms.txt`.
- Eloquent `saving` on pages/posts → Editor stores the authoritative scores.
- `Setting` / `Menu` / `Redirect` saved → page cache bust.
- Models opt in to cross-cutting behaviour with traits: `Auditable` (Logs), `TracksMediaUsage` (Media),
  `HasContent` + `HasRevisions` (Content).

## Request flow (public site)

`CanonicalHost` (301 to canonical host/https/slash) → `SecurityHeaders` (global) → `HandleRedirects`
(admin redirects, 410) → `PageCache` (anonymous GET) → `LogAccess` (terminate, cookieless) →
`SetFrontLocale` (`/tr/...` prefixes) → `ContentController` → theme view with `SeoData`.

Front-end routes are registered after all other routes, so project routes in `routes/web.php`
always win over the page catch-all.

## Scoring: one algorithm, two runtimes

`app/Modules/Editor/Scoring/*` (PHP, authoritative, stored on save) and
`resources/js/larawebkit/scoring.js` (browser, live) implement the same steps. Weights and
thresholds come from `config/larawebkit-editor.php` and are passed to the browser. The fixtures in
`tests/fixtures/scoring` are checked by Pest *and* by `npm test`, so the two cannot drift apart.
After an intentional change run `php tests/fixtures/scoring/generate.php > tests/fixtures/scoring/expected.json`.

## Images

Stored editor HTML keeps `<img data-id="{media id}">`. At render time `ContentImages` replaces them
with `srcset`/`sizes`/`width`/`height`/`loading="lazy"` tags from the current variants, so
`larawebkit:media:regenerate` never leaves stale markup behind. The hero image is rendered with
`fetchpriority="high"` and preloaded in `<head>`.

## Data that is never stored

- Raw IP addresses in the access log (daily-rotating keyed hash instead).
- AI prompts and outputs in the usage log (counts and cost only).
- Passwords and 2FA secrets in the audit log.
