<?php

namespace App\Modules\Content\Console;

use App\Modules\Content\Models\Page;
use App\Modules\Content\Models\Post;
use Illuminate\Console\Command;

/**
 * Scheduled publishing: content with a past published_at becomes visible by
 * itself; this (every minute from the scheduler) fires ContentPublished so
 * caches, sitemaps, llms.txt and IndexNow catch up.
 */
class AnnounceScheduledCommand extends Command
{
    protected $signature = 'larawebkit:content:announce';

    protected $description = 'Fire publish events for scheduled pages and posts whose time has come.';

    public function handle(): int
    {
        $count = 0;
        foreach ([Post::class, Page::class] as $model) {
            $model::published()->whereNull('announced_at')->each(function ($item) use (&$count) {
                $item->announceIfVisible();
                $count++;
            });
        }
        $this->info("Announced $count item(s).");

        return self::SUCCESS;
    }
}
