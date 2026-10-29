# Recipe: a new content type

Example: "Case studies" at `/case-studies/{slug}` with the full editor (scores, gate, revisions, media modal).

## Files to touch

1. **Migration** `database/migrations/…_create_case_studies_table.php` — copy the `$contentColumns` closure from
   `2026_01_01_000040_create_content_tables.php` (title, slug, locale, translation_group, content, summary, faq,
   sources, meta fields, keywords, status, published_at, announced_at, author_id, featured_media_id, scores,
   word_count, gate_override_reason, timestamps, soft deletes) plus your own columns. Add `unique(['locale', 'slug'])`.
2. **Model** `app/Modules/Content/Models/CaseStudy.php` (or your module):
   ```php
   use Auditable, HasContent, HasFactory, HasRevisions, SoftDeletes, TracksMediaUsage;
   protected $guarded = ['id', 'seo_score', 'human_score', 'geo_score', 'word_count', 'announced_at'];
   public function publicPath(): string { return localized_path('/case-studies/'.$this->slug, $this->locale); }
   protected function previousPublicPath(): ?string {
       return $this->wasChanged(['slug', 'locale']) ? localized_path('/case-studies/'.$this->getOriginal('slug'), $this->getOriginal('locale')) : null;
   }
   ```
   You get sanitized HTML, unique slugs, automatic 301s, revisions, media usage, publish events and audit logging.
3. **Morph alias** in `LarawebkitServiceProvider::boot()` → `'case_study' => CaseStudy::class`.
4. **Scores**: in `EditorServiceProvider::boot()` add the class to the `saving` loop that calls `ContentScorer::apply()`.
5. **Admin**: a resource like `PostResource` — `form()` returns `EditorSchema::components('post')` (or `'page'`),
   the Create/Edit pages `use InteractsWithContentEditor` and call `enforcePublishGate()` + `prepareContentData()`
   in `mutateFormDataBefore*()` exactly like `CreatePost` / `EditPost`. Add `RevisionsRelationManager` and
   `AuditLogsRelationManager` to `getRelations()`. Add a `fullAccessPermission()` and a permission
   (`manage case studies`) in `RolesAndPermissions`.
6. **Front end**: a controller action that queries `CaseStudy::published()->where('locale', app()->getLocale())`,
   builds `SeoData::forContent($item)` and renders `app(ThemeManager::class)->view('case-study')`.
   Register the route in `ContentServiceProvider::frontRoutes()` (before the page catch-all) and create
   `resources/views/themes/minimal/case-study.blade.php` (copy `post.blade.php`).
7. **SEO/GEO**: add a type to `SitemapController::TYPES`/`query()`, and the items to `LlmsTxt::index()`/`full()`.
8. **Translations** in en + tr.

## Tests to add

- Creating through the Filament page stores scores and sanitizes HTML (see `tests/Feature/Content/PostEditorTest.php`).
- Draft/scheduled items are 404, published ones render with canonical + JSON-LD (see `tests/Feature/Seo/FrontSeoTest.php`).
- Slug change creates a redirect; the item appears in the sitemap and `llms.txt`.
