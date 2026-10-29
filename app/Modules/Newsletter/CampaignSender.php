<?php

namespace App\Modules\Newsletter;

use App\Modules\Newsletter\Mail\CampaignMail;
use App\Modules\Newsletter\Models\Campaign;
use App\Modules\Newsletter\Models\CampaignSend;
use App\Modules\Newsletter\Models\Subscriber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sending a campaign: start() fixes the recipient list (confirmed, not unsubscribed),
 * then batch() sends up to `batch_size` pending e-mails per run until none are left.
 */
class CampaignSender
{
    public function start(Campaign $campaign): int
    {
        if (! in_array($campaign->status, ['draft', 'scheduled'], true)) {
            return 0;
        }

        DB::transaction(function () use ($campaign) {
            Subscriber::active()->when($campaign->locale, fn ($q, $locale) => $q->where('locale', $locale))
                ->select('id', 'email')->orderBy('id')
                ->chunk(1000, function ($subscribers) use ($campaign) {
                    CampaignSend::insertOrIgnore($subscribers->map(fn ($s) => ['campaign_id' => $campaign->id, 'subscriber_id' => $s->id, 'email' => $s->email, 'status' => 'pending'])->all());
                });
            $campaign->forceFill([
                'status' => 'sending',
                'started_at' => now(),
                'recipients_count' => $campaign->sends()->count(),
            ])->save();
        });

        return $campaign->recipients_count;
    }

    /** Start scheduled campaigns that are due. */
    public function startDue(): void
    {
        Campaign::where('status', 'scheduled')->where('scheduled_at', '<=', now())->get()->each(fn (Campaign $c) => $this->start($c));
    }

    /** Send one batch across sending campaigns (oldest first). Returns the number of e-mails sent. */
    public function batch(?int $size = null): int
    {
        $size ??= max(1, (int) config('larawebkit-newsletter.batch_size', 50));
        $sent = 0;

        foreach (Campaign::where('status', 'sending')->orderBy('started_at')->get() as $campaign) {
            $pending = $campaign->sends()->where('status', 'pending')->with('subscriber')->orderBy('id')->limit($size - $sent)->get();
            foreach ($pending as $send) {
                $subscriber = $send->subscriber;
                if (! $subscriber || ! $subscriber->confirmed_at || $subscriber->unsubscribed_at) {
                    $send->update(['status' => 'skipped']);

                    continue;
                }
                try {
                    Mail::to($send->email)->send(new CampaignMail($campaign, $subscriber));
                    $send->update(['status' => 'sent', 'sent_at' => now()]);
                    $campaign->increment('sent_count');
                    $sent++;
                } catch (Throwable $e) {
                    $send->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 500)]);
                    $campaign->increment('failed_count');
                }
            }
            if (! $campaign->sends()->where('status', 'pending')->exists()) {
                $campaign->forceFill(['status' => 'sent', 'finished_at' => now()])->save();
            }
            if ($sent >= $size) {
                break;
            }
        }

        return $sent;
    }

    public function sendTest(Campaign $campaign, string $email): void
    {
        $preview = new Subscriber(['email' => $email, 'locale' => $campaign->locale ?: app()->getLocale()]);
        $preview->id = 0;
        Mail::to($email)->send(new CampaignMail($campaign, $preview, test: true));
    }
}
