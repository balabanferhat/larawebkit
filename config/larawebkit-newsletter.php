<?php

return [

    /*
    | Campaigns are sent in small batches: every minute the scheduler queues one batch
    | (database queue, drained by the same cron). batch_size e-mails per minute keeps
    | shared-hosting mail limits happy (e.g. 50/min ≈ 3,000/hour). Ask your host.
    */
    'batch_size' => (int) env('NEWSLETTER_BATCH_SIZE', 50),

];
