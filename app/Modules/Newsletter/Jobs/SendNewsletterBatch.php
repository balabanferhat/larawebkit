<?php

namespace App\Modules\Newsletter\Jobs;

use App\Modules\Newsletter\CampaignSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/** Queued every minute by the scheduler while campaigns are sending (one at a time). */
class SendNewsletterBatch implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $uniqueFor = 120;

    public int $timeout = 50;

    public function handle(CampaignSender $sender): void
    {
        $sender->startDue();
        $sender->batch();
    }
}
