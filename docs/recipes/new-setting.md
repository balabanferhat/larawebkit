# Recipe: a new setting

Settings are typed values the site owner changes in **Admin → System → Settings**. Secrets (API keys,
passwords) never go here — they belong in `.env`.

1. Define it in `config/larawebkit-settings.php`:
   ```php
   'general.show_reading_time' => ['type' => 'bool', 'default' => true, 'group' => 'general'],
   ```
   Types: `string`, `text`, `bool`, `int`, `email`, `url`, `select` (with `options` array or `'locales'`/`'themes'`),
   `multiselect`, `media` (a media library id), `map` (key → value), `bots`. Optional `rules` (Laravel validation).
   A new `group` needs a label under `groups` in the translations.
2. Label (and optional help text) in **both** `lang/en/larawebkit-settings.php` and `lang/tr/larawebkit-settings.php`:
   `keys.general.show_reading_time`, `help.general.show_reading_time`.
3. Read it anywhere: `setting('general.show_reading_time')` (typed, cached, falls back to the default).
   Write it in code: `app(SettingsRepository::class)->set('general.show_reading_time', false)`.

The form is generated from the definitions, every change is audit-logged, and saving busts the page cache.

## Tests

`tests/Feature/Core/TranslationsTest.php` already fails if a label is missing. Add a behaviour test:

```php
it('hides the reading time when disabled', function () {
    app(SettingsRepository::class)->set('general.show_reading_time', false);
    $this->get('/blog/x')->assertDontSee('min read');
});
```
