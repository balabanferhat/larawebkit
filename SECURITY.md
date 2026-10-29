# Security policy

## Supported versions

| Version | Supported |
|---|---|
| 0.1.x | ✅ |

## Reporting a vulnerability

Please **do not** open a public issue. Use GitHub's private vulnerability reporting
("Security" tab → "Report a vulnerability") with steps to reproduce and the affected version.
You will get an answer within a few days; fixes are released as soon as possible and credited if you wish.

## Hardening built into the kit

- Admin accounts with roles/permissions, optional mandatory TOTP 2FA, login throttling, session revocation.
- CSRF on every form, honeypot + per-IP rate limit on public forms, security headers (HSTS over https).
- Server-side HTML sanitizing of editor content; uploads re-encoded (WebP) with metadata stripped.
- No raw IPs in the access log, no prompts in the AI log, no passwords or 2FA secrets in the audit log.
- Secrets only in `.env` — never in settings, prompts or logs.

When deploying: set `APP_DEBUG=false`, use https, keep `.env` outside the web root (document root = `public/`)
and run `php artisan larawebkit:deploy-check`.
