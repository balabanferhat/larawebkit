# Recipes

Step-by-step guides for extending larawebkit consistently. AI agents: read the matching recipe
before you start, follow it, and add a recipe when none fits.

| Recipe | Use it when you… |
|---|---|
| [new-content-type.md](new-content-type.md) | need another kind of content with editor, scores, revisions and public pages (e.g. "case studies") |
| [new-filament-resource.md](new-filament-resource.md) | need an admin screen for a model |
| [new-audit-logged-model.md](new-audit-logged-model.md) | add a model whose changes must appear in the audit log |
| [new-setting.md](new-setting.md) | need a value the site owner can change in the admin |
| [new-ai-action.md](new-ai-action.md) | add something the AI assistant can do |
| [new-theme.md](new-theme.md) | add a theme or child theme (themes/{name}, theme.json), its demo, or a page template |
| [new-block.md](new-block.md) | add a section block for pages (hero, pricing, …) or a page preset |
| [new-locale.md](new-locale.md) | add a language besides en and tr |
| [new-module.md](new-module.md) | add a feature that a project should be able to drop |

General rules (from `CLAUDE.md`): every UI string through `__()` in `lang/en` **and** `lang/tr`;
no secrets outside `.env` (add new variables to `.env.example` with empty values); optional services
must hide themselves when unconfigured; every feature has Pest tests and `php artisan test` stays green.
