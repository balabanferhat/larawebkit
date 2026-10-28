# Recipe: a new AI action

Example: "summarize" — produce a TL;DR from the article.

1. **Config** `config/larawebkit-ai.php` → `actions`:
   ```php
   'summarize' => ['tier' => 'cheap', 'max_tokens' => 400, 'queue' => false],
   ```
   `tier` picks the provider's `cheap` or `strong` model; `queue: true` runs it as a queued `AiTask` (the editor polls it).
2. **Prompt** `resources/prompts/summarize.md` (and optionally `summarize.tr.md`):
   ```markdown
   <!-- version: 1 -->
   # system
   You write 1-3 sentence summaries in {{language}} for "{{site_name}}". Return plain text only.

   # user
   {{text}}
   ```
   Placeholders are the input keys plus `language` and `site_name`. Projects override prompts by placing a file
   with the same name in `resources/prompts/overrides/`. Never put secrets or personal data in prompts.
3. **Call it**:
   - from PHP: `app(AiManager::class)->run('summarize', ['text' => $plain], auth()->user())`
     (check `->available('summarize')` first; it is false without keys, over budget, or when disabled);
   - from the editor: `POST /{admin}/larawebkit/ai/summarize` with JSON input (see `AiController`, add any new
     input key to its validation) and a button in `resources/views/editor/panel.blade.php` + a method in
     `lwkEditorPanel` (`resources/js/larawebkit/editor.js`) that calls `this.ai('summarize', {...})`.
4. **Translations**: `larawebkit-ai.actions.summarize` (+ any button label) in en and tr.

Usage (tokens, estimated cost, user) is logged automatically and counts towards the monthly budget.
Add a model price to `pricing` if you use a model that is not listed.

## Tests

Use the fake driver from `tests/Feature/Ai/AiLayerTest.php`:

```php
it('summarizes with the cheap model', function () {
    $driver = fakeAi();
    app(AiManager::class)->run('summarize', ['text' => 'Long text']);
    expect($driver->calls[0]['model'])->toBe('claude-haiku-5-5');
});
```
