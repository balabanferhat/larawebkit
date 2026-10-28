# Contributing

Thanks for helping! larawebkit aims to stay generic, small and easy for both humans and AI agents to extend.

1. Read [`CLAUDE.md`](CLAUDE.md) (rules) and [`docs/architecture.md`](docs/architecture.md).
2. Follow the matching recipe in [`docs/recipes`](docs/recipes); add a recipe if your change introduces a new pattern.
3. Every UI string goes through `__()` with **English and Turkish** translations.
4. No secrets, real API keys, customer data or code copied from private projects. New environment variables go to
   `.env.example` with an empty or dummy value.
5. Optional services must degrade gracefully (hidden or disabled, never a crash, when unconfigured).
6. Domain-specific features (medical, legal, …) belong in optional modules, not in core.
7. Add Pest tests. Before opening a pull request:
   ```bash
   php artisan test && npm test && vendor/bin/pint --test
   ```
8. Small, focused commits with clear messages in English.

Use the issue templates for bugs and feature requests. Report security problems privately (see [SECURITY.md](SECURITY.md)).
