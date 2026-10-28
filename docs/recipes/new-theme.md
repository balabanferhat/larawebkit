# Recipe: a new theme or page template

## Theme

1. Register it in `App\Modules\Themes\ThemeManager::themes()`:
   `'magazine' => ['label' => 'larawebkit-themes.magazine.label', 'description' => 'larawebkit-themes.magazine.description']`
   and add both keys to `lang/en|tr/larawebkit-themes.php`.
2. Views in `resources/views/themes/magazine/`. Only override what differs — every missing view falls back to
   `themes/minimal`. Start with `layout.blade.php` (extend `themes.minimal.layout` and override `@section('header')`
   like the docs theme does) and `home.blade.php`.
3. CSS entry `resources/css/themes/magazine.css`:
   ```css
   @import "tailwindcss";
   @import "./base.css";
   @source "../../views/themes";
   @source "../../views/components/lwk";
   :root { --accent: #b91c1c; }
   ```
   Add it to `input` in `vite.config.js`. Without a build the theme falls back to the inlined `base.css`.
4. Select it in **Settings → General → Theme**.

Keep themes fast and accessible: one `<h1>`, landmarks (`header`, `nav`, `main`, `footer`), visible focus,
WCAG AA contrast in light *and* dark (`[data-theme="dark"]`), no render-blocking third-party scripts,
images only through `<x-lwk.image>` (srcset, dimensions, lazy) and `:priority="true"` for the hero image.
Always include `<x-lwk.seo :seo="$seo" />` in the head and `<x-lwk.consent />` before `</body>`.

## Page template

Templates are chosen per page in the editor. To add one:

1. Add the name to `Page::TEMPLATES` and a label in `larawebkit-content.templates.<name>` (en + tr).
2. Create `resources/views/themes/minimal/templates/<name>.blade.php` (other themes may override it).
   Extend the theme's `page` view and fill `@section('after-content')`, or write a full layout like
   `themes/business/templates/landing.blade.php`.

## Tests

```php
it('renders the magazine theme', function () {
    app(SettingsRepository::class)->set('general.theme', 'magazine');
    $this->get('/')->assertOk()->assertSee('data-theme-name="magazine"', false);
});
```
