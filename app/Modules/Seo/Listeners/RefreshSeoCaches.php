<?php

namespace App\Modules\Seo\Listeners;

use App\Modules\Content\Events\ContentPublished;
use App\Modules\Content\Events\ContentSaved;
use App\Modules\Seo\CanonicalUrl;
use App\Modules\Seo\Http\Controllers\SitemapController;
use App\Modules\Seo\Http\Middleware\PageCache;
use App\Modules\Seo\IndexNow;

/** Cache bust on save, IndexNow ping when published content changes. */
class RefreshSeoCaches
{
    public function handleSaved(ContentSaved $event): void
    {
        PageCache::bust();
        SitemapController::flush();

        $content = $event->content;
        if ($content->isPublished() && ! $content->noindex && ! $content->wasRecentlyCreated && $content->announced_at) {
            app(IndexNow::class)->submit([CanonicalUrl::for($content->publicPath())]);
        }
    }

    public function handlePublished(ContentPublished $event): void
    {
        PageCache::bust();
        SitemapController::flush();
        if (! $event->content->noindex) {
            app(IndexNow::class)->submit([CanonicalUrl::for($event->content->publicPath())]);
        }
    }

    public function subscribe(): array
    {
        return [ContentSaved::class => 'handleSaved', ContentPublished::class => 'handlePublished'];
    }
}
