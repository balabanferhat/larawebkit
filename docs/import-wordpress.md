# Importing from WordPress

```bash
php artisan larawebkit:import:wordpress https://old-site.com --dry-run   # see what would happen
php artisan larawebkit:import:wordpress https://old-site.com             # import
```

The command reads the public WordPress REST API (`/wp-json/wp/v2`), 100 items per request, and imports:

| WordPress | larawebkit |
|---|---|
| users (authors) | author profiles (name, slug, bio, website) without a role, so they cannot log in to the admin; or `--author=you@example.com` to make one existing user the author of everything |
| categories, tags | categories, tags (same slugs) |
| pages | pages with the same parent tree, so `/about/team/` stays `/about/team` |
| posts | posts at `/blog/{slug}` with categories, tags, excerpt, featured image and publish date |
| images | downloaded once into the media library (WebP variants, alt text and caption kept); thumbnails like `photo-1024x683.jpg` are swapped for the full size |

Published and scheduled items keep their status; drafts and private items (only visible when the API returns
them) become drafts. Imported posts are not sent to IndexNow: search engines already know them.

## Excerpts and alt text

Hand-written excerpts are kept. Empty ones and WordPress's automatic ones (the first words of the body with
the paragraphs glued together) are built from the paragraphs of the cleaned body. Featured images without alt text get the post title, or the
media title when it describes the picture; the report lists every image without alt text so you can describe
them in the media library.

## Markup

Rendered Gutenberg or classic-editor HTML is cleaned to the editor's markup: block comments, classes, inline
styles, wrappers and empty paragraphs go; `h1` in the body becomes `h2`, `h5`/`h6` become `h4`; buttons become
links; YouTube, Vimeo and other embeds or iframes become a plain link (no third-party scripts); links to the old
site become relative, so they keep working on the new domain. Everything then goes through the usual
sanitizer.

## URLs

Redirect targets use the language of the import (`--locale`). To keep WordPress post-name permalinks
(`/hello/`) set `POST_PREFIX=` (empty) in `.env` before importing: posts then live at `/{slug}` and need no
redirect (a page with the same path wins). Otherwise:

Pages keep their paths. An empty page is skipped and its old address sent home, unless that address is a route
here (an empty WordPress "Blog" page at `/blog/`, the events path): then it is only skipped, so the post listing
stays reachable. Changing `POST_PREFIX` or `BLOG_PATH` later? Run the import again: the importer's own redirects
are updated to the new addresses (redirects you made or edited in the admin are left alone). Posts, categories and tags move to `/blog/...`; for every old URL that changes (e.g.
`/2024/05/hello/` → `/blog/hello`, `/category/news/` → `/blog/category/news`) a 301 redirect is added
(Content → Redirects, marked automatic). Point the domain at the new site after the import and old links keep
working.

## Re-running

Every item is remembered in the `import_map` table. A second run creates nothing twice: unchanged items are
skipped, items edited in WordPress since the last run are updated, and items you deleted here are not brought
back. You can run it again right before switching the domain to pick up the latest posts.

## Options

| Option | |
|---|---|
| `--dry-run` | fetch and count, change nothing, download nothing |
| `--skip-category=slug` | do not import posts in this category (repeatable); the category itself is skipped too |
| `--gone` | the old URLs of skipped posts and categories answer **410 Gone** (search engines drop them) instead of 404; change one to a 301 in Content → Redirects and re-runs keep it |
| `--keep-empty-pages` | also import pages without content; by default theme parts kept as pages (slider, header, an empty "Blog" page) are skipped and their old address goes to the home page |
| `--locale=tr` | language of the imported content (default: the site's default language) |
| `--author=email` | one existing user becomes the author of everything |
| `--no-images` | keep image URLs instead of downloading |
| `--media-host=cdn.example.net` | also download images from this host (repeatable); by default only the site's own host and its subdomains |

## Without the REST API

If the API is blocked (security plugin, private site), save the responses as one JSON file and pass its path:

```json
{
  "site": "https://old-site.com",
  "users": [ ... ], "categories": [ ... ], "tags": [ ... ],
  "pages": [ ... ], "posts": [ ... ], "media": [ ... ]
}
```

Each list has the REST API's shape (`/wp-json/wp/v2/posts?per_page=100&page=1` and so on, concatenated).
Images are still downloaded from the site unless you pass `--no-images`.

Large sites: run it from SSH rather than a web cron, and with `memory_limit=256M`; images are processed one at a
time.
