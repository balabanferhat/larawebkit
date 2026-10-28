# Recipe: a new locale

Example: German (`de`).

1. `config/larawebkit.php` → `'locales' => ['en' => 'English', 'tr' => 'Türkçe', 'de' => 'Deutsch']`.
2. Copy `lang/en/larawebkit*.php` to `lang/de/` and translate every value (keep the keys and `:placeholders`).
   `tests/Feature/Core/TranslationsTest.php` checks en/tr parity; extend it to `de` if you ship it in core.
3. Language-dependent heuristics:
   - clichés: `config/larawebkit-editor/cliches/de.php` (a plain list of phrases),
   - reader words for the human score: `config/larawebkit-editor.php` → `human.reader_words.de` (`sie`, `ihr`, `du`, …).
4. Optional prompt variants: `resources/prompts/draft.de.md` (otherwise `draft.md` with `{{language}}` = "Deutsch").
5. Filament ships German UI strings; Laravel validation messages can be published with `php artisan lang:publish`
   or added from a community package.
6. Enable it in **Settings → General → Active languages**. Content in that locale is served under `/de/...`,
   translations are linked with the "Translation of" field (hreflang is emitted automatically).

## Tests

```php
it('serves German content under /de', function () {
    app(SettingsRepository::class)->set('general.active_locales', ['en', 'de']);
    Post::factory()->published()->create(['locale' => 'de', 'slug' => 'hallo']);
    $this->get('/de/blog/hallo')->assertOk()->assertSee('<html lang="de"', false);
});
```
