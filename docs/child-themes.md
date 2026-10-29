# Child themes

A child theme changes a bundled theme without copying it. It lists the theme it builds on as its `parent`.
Every view, stylesheet, translation and design default it leaves out comes from the parent, then from the
parent's parent, down to `minimal`. Updates to larawebkit then keep reaching your site: only the files you
override are yours to maintain.

This guide builds `themes/acme-news`, a child of **Magazine**. The same steps work for any parent. The reference
for every `theme.json` field is [recipes/new-theme.md](recipes/new-theme.md).

## 1. The folder and theme.json

```
themes/acme-news/
├── theme.json
├── screenshot.webp          # optional, 1200 × 900, shown in Appearance → Themes
├── src/theme.css            # your CSS on top of the parent's
├── assets/theme.css         # built from src/ (commit it: hosting needs no Node)
├── lang/en/theme.php        # name, description and any strings your views use
├── lang/tr/theme.php
└── views/                   # only the views you change
```

```json
{
    "name": "Acme News",
    "description": "Magazine, with our masthead and colours.",
    "version": "1.0.0",
    "author": "Your name",
    "parent": "magazine",
    "supports": { "blocks": "*" },
    "defaults": { "primary": "#0f766e" }
}
```

`defaults` only needs the values you change: colours, `font_pair` (for example `classic`, `editorial` or
`system`) and `radius`. The site owner can still override them in **Appearance**.

## 2. CSS on top of the parent

```css
/* themes/acme-news/src/theme.css */
@import "theme:magazine/src/theme.css";

.lwk-masthead .lwk-brand-name { letter-spacing: -0.03em; }
.lwk-rule-title { color: var(--brand-strong); }
```

Use the design tokens (`--brand`, `--brand-strong`, `--link`, `--text`, `--muted`, `--bg`, `--bg-soft`,
`--surface`, `--border`, `--font-heading`, `--font-body`, `--radius`) instead of fixed colours. They are already
adjusted for WCAG AA contrast in light and dark mode, and they follow the owner's Appearance settings. Do not
redefine `--font-heading` in CSS: that would override the font the owner picks.

Build and check:

```bash
php artisan larawebkit:themes:build          # writes themes/*/assets/theme.css
php artisan larawebkit:themes:build --check  # what CI runs: fails if assets/ is out of date
```

## 3. Override a view

Copy the one view you need from the parent (or from `themes/minimal/views` when the parent does not have it)
into the same path under `views/`, and change it there. Views refer to each other as `theme::name`, which
searches the chain, so your override is picked up everywhere.

| Change | File in your theme |
|---|---|
| Header or footer | `views/partials/header.blade.php`, `views/partials/footer.blade.php` |
| Front page (when the site has no page called `home`) | `views/home.blade.php` |
| Blog, category and tag archives | `views/blog.blade.php` |
| Article | `views/post.blade.php` |
| Search, 404 / 410 | `views/search.blade.php`, `views/errors/404.blade.php` |
| One section block | `views/blocks/{type}.blade.php`, e.g. `views/blocks/latest_posts.blade.php` |

To change a parent view only a little, extend it instead of copying it: `@extends('theme-magazine::post')`
and override a single `@section`. That is how Magazine's own `post.blade.php` builds on Minimal.

## 4. Pages from block presets

Magazine builds its front page, archives, search and 404 page from **block presets** in
`App\Modules\Blocks\BlockPresets`:

- `magazine`: lead story, latest rail, more stories, newsletter;
- `archive`: the newsletter under archives and search;
- `not_found`: latest articles on the 404 page and on searches without results.

A child theme can render a preset anywhere with `<x-lwk.blocks :blocks="\App\Modules\Blocks\BlockPresets::blocks('archive')" />`,
or with its own list of blocks in the same `[['type' => ..., 'data' => [...]]]` shape. For a front page that
editors change themselves, create a page with the path `home` from the "Magazine front page" preset in the page
editor: it then replaces `views/home.blade.php`.

## 5. Strings and languages

Every visible string goes through `__()`. Strings of your own live in `lang/{en,tr}/theme.php` and are used as
`__('theme-acme-news::theme.key')`. The test suite checks that both languages have the same keys.

## 6. Activate, test, ship

1. **Appearance → Themes → Activate**. From the command line:
   `php artisan tinker --execute="app(App\Modules\Themes\ThemeManager::class)->activate('acme-news')"`.
2. `php artisan larawebkit:demo acme-news` fills a copy of the site with sample content if your theme has a
   `demo.php`. Without one, the parent's demo is used. Demo sites show an "example content" notice.
3. Measure: `node scripts/lighthouse/run.mjs --themes=acme-news` checks the budgets CI enforces.
4. Commit `themes/acme-news` including `assets/`. Deploy as usual; nothing to build on the server.

Keep overrides few. After a larawebkit update, compare each overridden view with the parent's version
(`git diff` on `themes/magazine/views/...`) and carry over fixes.
