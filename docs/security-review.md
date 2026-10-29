# Security review (v0.3 preparation)

A review of the public attack surface before v0.3: secrets, everything a visitor can send (search, forms,
newsletter, events, member accounts), uploads, and dependencies. Everything found has a regression test, unless
it is listed under "open" below.

## Method

- **Secrets:** pattern scan of every tracked file (API keys for Anthropic / OpenAI / AWS / GitHub / Slack /
  Google, private keys), a check that no `.env` file is tracked, and that `.env.example` has no real values.
  `RepoHygieneTest` keeps every `env()` variable listed in `.env.example`.
- **Dependencies:** `composer audit` and `npm audit`: no known advisories.
- **Inputs:** each public route was called with what a script would send instead of a browser: arrays instead of
  strings (`q[]=x`, `email[]=x`, `_lwk_ts[]=x`), LIKE wildcards, oversized values and wrong file types.
  Rendering paths were read for unescaped output (`{!! !!}`).
- **Uploads:** form file fields, the media library, theme zip upload, and lead-magnet / backup downloads.

## Findings

| # | Area | Finding | Severity | Status |
|---|---|---|---|---|
| 1 | Search | `/search?q[]=x` raised a PHP error (500): the query string was cast to a string without checking its type. | Low (error page, no data) | Fixed: a non-string `q` is an empty search. Test in `AdminConveniencesTest`. |
| 2 | Search | `%` and `_` were LIKE wildcards: `?q=%` listed every post. It was not an injection (the value is bound), but it let anyone enumerate the archive and made the result highlighting misleading. | Low | Fixed: escaped with `ESCAPE '!'`, which behaves the same on SQLite, MySQL and MariaDB. Checked on SQLite and MariaDB. |
| 3 | Public forms (all) | The anti-spam time token sent as an array (`_lwk_ts[]=x`) raised a PHP error before the request was rejected. | Low | Fixed: rejected like any bot. Test in `AdminConveniencesTest`. |
| 4 | Member password reset | `?email[]=x` on the reset page raised a PHP error. | Low | Fixed. Test in `MembersTest`. |
| 5 | Form uploads | The stored file got the visitor's extension. Laravel's `mimes` rule already refuses a name that does not match the content, so nothing exploitable got through. | Informational | Hardened: the extension now comes from the content and must be on the allow list (`FieldTypes::FILE_TYPES`). Test: PDF bytes named `.php` are refused. |

## Checked and fine

- **Output escaping:** search highlighting escapes the text first and the term (`preg_quote(e($q))`), and the
  replacement is constant. Post and page HTML passes `HtmlSanitizer` (allow list; no scripts, styles, event
  handlers or iframes) on save. Event descriptions, newsletter bodies and rich-text blocks are sanitised too.
  E-mail templates escape Markdown control characters in user values.
- **Forms, newsletter, events, member registration:** the public-form middleware (honeypot, minimum fill time,
  rate limit `PUBLIC_FORM_RATE_LIMIT` per minute and IP) plus server-side validation of every field. Answers do
  not reveal whether an address exists (newsletter, password reset, event registration).
- **Member login:** throttled (10/min), a separate `member` guard (members never reach the admin), session
  regenerated on login, password hashing via Laravel.
- **Form uploads:** private disk, a UUID directory per file, size limit per field and globally
  (`FORMS_MAX_FILE_KB`), an extension allow list without executables, files deleted with the submission.
- **Media uploads:** the type is sniffed from the content (`finfo`); only JPEG / PNG / WebP / GIF are accepted (no
  SVG); a memory budget is enforced before decoding; originals sit on the private disk, and only WebP variants are
  public.
- **Theme zip upload:** off by default (`THEMES_ALLOW_UPLOAD`), super-admin only. Paths are checked (no `..`, no
  absolute paths, no symlinks), with an extension allow list and size limits.
- **Downloads:** lead magnets, event calendar files and registrant pages use signed URLs (expiring where it
  matters). Backups can only be downloaded by super-admins, and file names are checked against
  `^[A-Za-z0-9._-]+\.zip$` (no path traversal).
- **Shared preview links:** signed, expire after `PREVIEW_LINK_HOURS`, `noindex` and `no-store`, and only cover
  `post` / `page` ids.
- **Events:** the online meeting link is encrypted at rest and appears only on the registrant's signed page and
  in their e-mails, never in the event page, the JSON-LD or the calendar file sent without a registration.

## Open (accepted or for the site owner)

- **Theme zips contain PHP (Blade).** When upload is switched on, a super-admin can install code. This is by
  design and documented; keep it off on sites where admins should not run code.
- **Content-Security-Policy** for the public site is opt-in (`SECURITY_FRONT_CSP`), because inline analytics and
  embeds differ per site. Recommended once the site's third parties are known.
- **Search has no rate limit of its own.** It is an uncached `LIKE` query, limited to 30 results and 100
  characters; the page cache does not cover it. Add `throttle` to the route if a site sees abuse.
- **`APP_KEY`** signs every link above and encrypts the meeting links and 2FA secrets. Rotating it invalidates
  all of them. Keep `.env` outside backups and outside the web root (see `docs/backups.md`).
- **Legal texts** (privacy policy, consent wording) are neutral placeholders and need review by the site owner.
