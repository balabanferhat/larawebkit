# Recipe: a new theme (or child theme)

Themes work like WordPress themes: every folder in `themes/` with a `theme.json` is a theme, discovered
automatically. A theme can name a **parent**; any view, asset, translation or design default it does not define
comes from the parent, then from `minimal` (the root of the bundled themes). Activate a theme in
**Appearance → Themes**.

## 1. Create the folder

```
themes/acme/
├── theme.json            # required
├── screenshot.webp       # 1200 × 900, shown on the Themes page
├── views/                # Blade views, only the ones you change
├── src/theme.css         # CSS source
├── assets/theme.css      # prebuilt CSS (committed, generated from src/)
├── lang/{en,tr}/theme.php  # name and description (+ any strings your views need)
└── demo.php              # optional: content for `php artisan larawebkit:demo acme`
```

`theme.json`:

```json
{
    "name": "Acme",
    "description": "Our company site.",
    "version": "1.0.0",
    "author": "Acme Ltd",
    "parent": "business",
    "supports": { "blocks": "*" },
    "defaults": { "primary": "#0f766e", "accent": "#f59e0b", "font_pair": "jakarta", "radius": "large" }
}
```

- `parent`: inherit everything from that theme (chains work: `acme → business → minimal`).
- `supports.blocks`: `"*"` or a list such as `["hero", "features", "cta", "faq", "rich_text"]` to limit the section
  blocks offered in the page editor.
- `defaults`: design tokens used until the site owner sets their own in **Appearance**. Optional `light` / `dark`
  objects set the neutral palette (`bg`, `bg_soft`, `surface`, `text`, `muted`, `border`). Font pairs:
  `system`, `inter`, `jakarta`, `grotesk`, `fraunces`, `editorial`; radius: `none`, `small`, `medium`, `large`.

## 2. Views

Look views up with `app(\App\Modules\Themes\ThemeManager::class)->view('post')` (`theme::post`), which searches the
chain. To extend one specific theme's view, use its namespace: `@extends('theme-minimal::post')`.
Views you will most often override: `layout`, `partials/header`, `partials/footer`, `home`, `post`, `page`,
`blog`, `errors/404`, and `blocks/{type}` to restyle a single section block.
Theme strings: `__('theme-acme::theme.some_key')` from `themes/acme/lang/{locale}/theme.php`.

## 3. CSS

Themes are plain CSS on design tokens (custom properties generated from Appearance), so a brand change needs no
rebuild and production needs no Node:

```css
/* themes/acme/src/theme.css */
@import "theme:business/src/theme.css";   /* the parent's CSS (or theme:minimal/src/theme.css) */

:root { --t-heading-weight: 800; --t-container: 80rem; }
.lwk-footer { background: #0b1120; }
```

Use the tokens instead of fixed colours: `--brand`, `--on-brand`, `--brand-soft`, `--brand-strong`, `--link`,
`--accent`, `--bg`, `--bg-soft`, `--surface`, `--text`, `--muted`, `--border`, `--radius(-sm|-lg)`,
`--font-heading`, `--font-body`, `--font-ui`. Text colours are adjusted to WCAG AA contrast automatically.

Build and commit the result:

```bash
php artisan larawebkit:themes:build acme   # bundles @imports and minifies into assets/theme.css
php artisan larawebkit:themes:build --check   # CI: fails if a committed assets/theme.css is out of date
```

A child theme without `src/theme.css` simply uses its parent's prebuilt CSS. `theme.js` is shared the same way
(`themes/minimal/assets/theme.js`: mobile menu, dropdowns, dark-mode switch, lightbox, click-to-load video).
Activation copies `assets/` to `public/themes/{name}`; until then (or if copying is not allowed) a PHP route serves
the files. After deploying run `php artisan larawebkit:themes:publish`.

## 4. Demo content (optional)

`demo.php` returns a closure that receives a `DemoBuilder` (see `themes/business/demo.php`): settings, generated
images, authors, categories, posts, pages built from blocks and menus. Put the texts in
`themes/acme/lang/{en,tr}/demo.php` and read them with `$demo->t('key')`.

## 5. Uploading a zipped theme

Off by default: a theme contains Blade views, i.e. PHP that runs on your server. Set `THEMES_ALLOW_UPLOAD=true`
to let **super-admins** upload a `.zip` in Appearance → Themes. The archive must contain `theme.json` at its root or
in one top folder; paths are checked (no `..`, no absolute paths, no symlinks) and only these file types are
accepted: `config('larawebkit-themes.allowed_extensions')`.

## Rules

Keep themes fast and accessible: one `<h1>`, landmarks (`header`, `nav`, `main`, `footer`), visible focus,
WCAG AA contrast in light **and** dark (`[data-theme="dark"]`), no render-blocking third-party scripts, images only
through `<x-lwk.image>` (srcset, dimensions, lazy) with `:priority="true"` for the hero. Always include
`<x-lwk.seo :seo="$seo" />` and `<x-lwk.head />` in the head and `<x-lwk.consent />` before `</body>` (the minimal
layout does this). Measure with Lighthouse (`docs/lighthouse/`).

## Page templates

Templates are chosen per page in the editor. Add the name to `Page::TEMPLATES` and a label in
`larawebkit-content.templates.<name>` (en + tr), then create `themes/minimal/views/templates/<name>.blade.php`
(other themes may override it). For most landing pages, use section blocks and a preset instead (see
`new-block.md`).

## Tests

```php
it('renders the acme theme', function () {
    app(SettingsRepository::class)->set('general.theme', 'acme');
    $this->get('/')->assertOk()->assertSee('data-theme-name="acme"', false);
});
```
