<?php

namespace App\Modules\Seo;

use App\Models\User;
use App\Modules\Content\Models\Page;
use App\Modules\Content\Models\Post;
use App\Modules\Media\Models\Media;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Everything the <head> needs for one URL: title, description, canonical,
 * robots, Open Graph / Twitter, hreflang alternates and JSON-LD graph.
 * Built by controllers, rendered by resources/views/components/lwk/seo.blade.php.
 */
class SeoData
{
    public string $title = '';

    public string $description = '';

    public string $canonical = '';

    public bool $noindex = false;

    public string $type = 'website';

    public ?Media $image = null;

    public ?string $imageUrl = null;

    /** @var array<string, string> hreflang => url */
    public array $alternates = [];

    /** @var list<array<string, mixed>> */
    public array $schema = [];

    /** @var list<array{name: string, url: string}> */
    public array $breadcrumbs = [];

    public ?Model $content = null;

    public ?string $prev = null;

    public ?string $next = null;

    public static function siteName(): string
    {
        return (string) setting('general.site_name', config('app.name'));
    }

    public static function make(string $title, string $path, ?string $description = null): self
    {
        $seo = new self;
        $seo->title = $title;
        $seo->description = Str::limit((string) ($description ?: setting('seo.default_meta_description') ?: setting('general.tagline')), 160, '…');
        $seo->canonical = CanonicalUrl::for($path);
        $seo->schema = [self::organization(), self::website()];
        $seo->breadcrumbs = [['name' => __('larawebkit-front.home'), 'url' => CanonicalUrl::for(localized_path('/'))]];

        return $seo;
    }

    public static function forHome(): self
    {
        $seo = self::make(self::siteName(), localized_path('/'));
        $seo->title = trim(self::siteName().' '.self::separator().' '.setting('general.tagline'), ' '.self::separator());
        $seo->breadcrumbs = [];
        $seo->alternates = self::localeAlternates(fn (string $l) => localized_path('/', $l));

        return $seo;
    }

    public static function forListing(string $title, string $path, ?Paginator $paginator = null, ?string $description = null): self
    {
        $seo = self::make($title, $path, $description);
        $seo->breadcrumbs[] = ['name' => $title, 'url' => $seo->canonical];
        if ($paginator && $paginator->currentPage() > 1) {
            // Pagination pages are noindex by default (no thin duplicates), but followed.
            $seo->noindex = true;
            $seo->canonical = CanonicalUrl::for($path).'?page='.$paginator->currentPage();
        }
        if ($paginator) {
            $seo->prev = $paginator->previousPageUrl();
            $seo->next = $paginator->nextPageUrl();
        }

        return $seo;
    }

    public static function forAuthor(User $author): self
    {
        $seo = self::make($author->name, localized_path('/author/'.$author->slug), $author->bio ? Str::limit($author->bio, 160) : null);
        $seo->type = 'profile';
        $seo->image = $author->avatar;
        $seo->breadcrumbs[] = ['name' => $author->name, 'url' => $seo->canonical];
        $seo->schema[] = ['@type' => 'ProfilePage', '@id' => $seo->canonical.'#profile', 'mainEntity' => self::person($author)];

        return $seo;
    }

    public static function forContent(Model $content, ?string $canonicalPath = null): self
    {
        /** @var Post|Page $content */
        $seo = self::make($content->seoTitle(), $canonicalPath ?? $content->publicPath(), $content->seoDescription());
        $seo->content = $content;
        $seo->noindex = (bool) $content->noindex;
        if ($content->canonical_url) {
            $seo->canonical = $content->canonical_url;
        }
        $seo->image = $content->featuredMedia;
        $seo->type = $content instanceof Post ? 'article' : 'website';

        if ($content instanceof Page) {
            foreach ($content->ancestors() as $ancestor) {
                $seo->breadcrumbs[] = ['name' => $ancestor->title, 'url' => CanonicalUrl::for($ancestor->publicPath())];
            }
        } else {
            $seo->breadcrumbs[] = ['name' => __('larawebkit-front.blog'), 'url' => CanonicalUrl::for(localized_path('/blog'))];
        }
        $seo->breadcrumbs[] = ['name' => $content->title, 'url' => $seo->canonical];

        $translations = $content->translations();
        if ($translations->isNotEmpty()) {
            $seo->alternates = [$content->locale => CanonicalUrl::for($content->publicPath())];
            foreach ($translations as $t) {
                $seo->alternates[$t->locale] = CanonicalUrl::for($t->publicPath());
            }
            $default = (string) setting('general.default_locale', 'en');
            $seo->alternates['x-default'] = $seo->alternates[$default] ?? $seo->alternates[$content->locale];
        }

        if ($content instanceof Post || ($content instanceof Page && $content->template !== 'legal')) {
            $seo->schema[] = self::article($content, $seo);
        }
        if ($faq = $content->faqItems()) {
            $seo->schema[] = [
                '@type' => 'FAQPage',
                '@id' => $seo->canonical.'#faq',
                'mainEntity' => array_map(fn ($item) => [
                    '@type' => 'Question',
                    'name' => $item['question'],
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['answer']],
                ], $faq),
            ];
        }

        return $seo;
    }

    public function noindex(bool $value = true): self
    {
        $this->noindex = $value;

        return $this;
    }

    public static function separator(): string
    {
        return (string) setting('seo.title_separator', '–');
    }

    public function fullTitle(): string
    {
        $site = self::siteName();

        return $this->title === $site || str_contains($this->title, $site) ? $this->title : $this->title.' '.self::separator().' '.$site;
    }

    /** Absolute URL of the 1200×630 Open Graph image (featured → default setting). */
    public function ogImageUrl(): ?string
    {
        if ($this->imageUrl) {
            return $this->imageUrl;
        }
        $media = $this->image ?? (setting('seo.default_og_image') ? Media::find(setting('seo.default_og_image')) : null);

        return $media ? app(OgImage::class)->url($media) : null;
    }

    /** @return array<string, mixed> */
    public function jsonLd(): array
    {
        $graph = $this->schema;
        if (count($this->breadcrumbs) > 1) {
            $graph[] = [
                '@type' => 'BreadcrumbList',
                '@id' => $this->canonical.'#breadcrumb',
                'itemListElement' => array_map(fn ($crumb, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $crumb['name'], 'item' => $crumb['url']], $this->breadcrumbs, array_keys($this->breadcrumbs)),
            ];
        }

        return ['@context' => 'https://schema.org', '@graph' => $graph];
    }

    /** @return array<string, string> locale => url for the active locales */
    private static function localeAlternates(callable $path): array
    {
        $active = (array) setting('general.active_locales', ['en']);
        if (count($active) < 2) {
            return [];
        }
        $out = [];
        foreach ($active as $l) {
            $out[$l] = CanonicalUrl::for($path($l));
        }
        $out['x-default'] = CanonicalUrl::for($path((string) setting('general.default_locale', 'en')));

        return $out;
    }

    /** @return array<string, mixed> */
    public static function organization(): array
    {
        $type = setting('organization.type', 'Organization') === 'Person' ? 'Person' : 'Organization';
        $logo = setting('organization.logo') ?: setting('appearance.logo');
        $logoMedia = $logo ? Media::find($logo) : null;

        return array_filter([
            '@type' => $type,
            '@id' => CanonicalUrl::for('/').'#organization',
            'name' => setting('organization.name') ?: self::siteName(),
            'url' => CanonicalUrl::for('/'),
            'logo' => $logoMedia && $type === 'Organization' ? ['@type' => 'ImageObject', 'url' => $logoMedia->url(640)] : null,
            'image' => $logoMedia && $type === 'Person' ? $logoMedia->url(640) : null,
            'email' => setting('general.contact_email') ?: null,
            'telephone' => setting('organization.phone') ?: null,
            'address' => setting('organization.address') ?: null,
            'sameAs' => array_values(array_filter((array) setting('social.links', []))) ?: null,
        ]);
    }

    /** @return array<string, mixed> */
    public static function website(): array
    {
        return [
            '@type' => 'WebSite',
            '@id' => CanonicalUrl::for('/').'#website',
            'url' => CanonicalUrl::for('/'),
            'name' => self::siteName(),
            'inLanguage' => app()->getLocale(),
            'publisher' => ['@id' => CanonicalUrl::for('/').'#organization'],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => ['@type' => 'EntryPoint', 'urlTemplate' => CanonicalUrl::for(localized_path('/search')).'?q={search_term_string}'],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /** @return array<string, mixed> */
    public static function person(User $user): array
    {
        return array_filter([
            '@type' => 'Person',
            '@id' => CanonicalUrl::for('/author/'.$user->slug).'#person',
            'name' => $user->name,
            'url' => CanonicalUrl::for(localized_path('/author/'.$user->slug)),
            'jobTitle' => $user->job_title ?: null,
            'description' => $user->bio ?: null,
            'knowsAbout' => $user->expertise ? array_map('trim', explode(',', $user->expertise)) : null,
            'image' => $user->avatar?->url(640),
            'sameAs' => array_values(array_filter((array) $user->same_as)) ?: null,
        ]);
    }

    /** @return array<string, mixed> */
    private static function article(Model $content, self $seo): array
    {
        /** @var Post|Page $content */
        return array_filter([
            '@type' => $content instanceof Post ? 'BlogPosting' : 'Article',
            '@id' => $seo->canonical.'#article',
            'headline' => Str::limit($content->title, 110, ''),
            'description' => $seo->description,
            'url' => $seo->canonical,
            'mainEntityOfPage' => $seo->canonical,
            'inLanguage' => $content->locale,
            'datePublished' => $content->published_at?->toIso8601String(),
            'dateModified' => $content->updated_at?->toIso8601String(),
            'image' => $content->featuredMedia ? [$content->featuredMedia->url(1280)] : null,
            'author' => $content->author ? self::person($content->author) : ['@id' => CanonicalUrl::for('/').'#organization'],
            'publisher' => ['@id' => CanonicalUrl::for('/').'#organization'],
            'wordCount' => $content->word_count ?: null,
            'keywords' => $content->keywords ? implode(', ', $content->keywords) : null,
            'articleSection' => $content instanceof Post ? $content->categories->pluck('name')->first() : null,
            'isAccessibleForFree' => $content instanceof Post && $content->members_only ? false : null,
        ], fn ($v) => $v !== null);
    }
}
