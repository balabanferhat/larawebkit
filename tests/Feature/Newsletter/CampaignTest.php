<?php

use App\Modules\Newsletter\CampaignSender;
use App\Modules\Newsletter\Filament\Resources\Campaigns\Pages\EditCampaign;
use App\Modules\Newsletter\Jobs\SendNewsletterBatch;
use App\Modules\Newsletter\Mail\CampaignMail;
use App\Modules\Newsletter\Mail\Welcome;
use App\Modules\Newsletter\Models\Campaign;
use App\Modules\Newsletter\Models\Subscriber;
use App\Modules\Settings\SettingsRepository;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;

function subscribers(int $confirmed, int $pending = 0, int $unsubscribed = 0, string $locale = 'en'): void
{
    foreach (range(1, $confirmed) as $i) {
        Subscriber::create(['email' => "c$i-$locale@example.com", 'token' => Str::random(64), 'locale' => $locale, 'confirmed_at' => now()]);
    }
    foreach (range(1, max(1, $pending)) as $i) {
        if ($pending) {
            Subscriber::create(['email' => "p$i@example.com", 'token' => Str::random(64), 'locale' => $locale]);
        }
    }
    foreach (range(1, max(1, $unsubscribed)) as $i) {
        if ($unsubscribed) {
            Subscriber::create(['email' => "u$i@example.com", 'token' => Str::random(64), 'locale' => $locale, 'confirmed_at' => now(), 'unsubscribed_at' => now()]);
        }
    }
}

function campaign(array $attributes = []): Campaign
{
    return Campaign::create($attributes + ['subject' => 'October news', 'preheader' => 'What is new', 'body' => '<h2>Hello</h2><p>News of the month.</p>']);
}

it('sends only to confirmed subscribers, in batches, and logs every send', function () {
    Mail::fake();
    subscribers(confirmed: 5, pending: 2, unsubscribed: 1);
    $campaign = campaign();
    $sender = app(CampaignSender::class);

    expect($sender->start($campaign))->toBe(5);
    expect($sender->batch(2))->toBe(2);
    expect($campaign->fresh())->status->toBe('sending')->sent_count->toBe(2);

    $sender->batch(2);
    $sender->batch(2);
    expect($campaign->fresh())->status->toBe('sent')->sent_count->toBe(5)->finished_at->not->toBeNull();
    Mail::assertSent(CampaignMail::class, 5);
    Mail::assertNotSent(CampaignMail::class, fn ($m) => str_starts_with($m->subscriber->email, 'p') || str_starts_with($m->subscriber->email, 'u'));
    expect($campaign->sends()->where('status', 'sent')->count())->toBe(5);
});

it('skips people who unsubscribed while a campaign was sending, and limits by language', function () {
    Mail::fake();
    subscribers(confirmed: 2);
    subscribers(confirmed: 3, locale: 'tr');
    $campaign = campaign(['locale' => 'tr']);
    $sender = app(CampaignSender::class);

    expect($sender->start($campaign))->toBe(3);
    Subscriber::where('email', 'c1-tr@example.com')->update(['unsubscribed_at' => now()]);
    $sender->batch(10);

    expect($campaign->fresh()->sent_count)->toBe(2)
        ->and($campaign->sends()->where('status', 'skipped')->count())->toBe(1);
});

it('adds one-click unsubscribe headers and link, and counts unsubscribes per campaign', function () {
    subscribers(confirmed: 1);
    $campaign = campaign();
    $subscriber = Subscriber::first();
    $mail = new CampaignMail($campaign, $subscriber);

    $headers = $mail->headers()->text;
    expect($headers['List-Unsubscribe'])->toBe('<'.$subscriber->unsubscribeUrl().'>')
        ->and($headers['List-Unsubscribe-Post'])->toBe('List-Unsubscribe=One-Click')
        ->and($mail->render())->toContain(e($subscriber->unsubscribeUrl()))->toContain('News of the month')->not->toContain('<img src="http');

    Mail::fake();
    app(CampaignSender::class)->start($campaign);
    app(CampaignSender::class)->batch();

    // RFC 8058: the mail client POSTs to the URL without a CSRF token.
    $this->post($subscriber->unsubscribeUrl())->assertOk();
    expect($subscriber->fresh()->unsubscribed_at)->not->toBeNull()
        ->and($campaign->fresh()->unsubscribedCount())->toBe(1);

    $this->post(route('newsletter.unsubscribe.signed', ['subscriber' => $subscriber->id]))->assertForbidden();
});

it('starts scheduled campaigns from the scheduler job', function () {
    Mail::fake();
    subscribers(confirmed: 2);
    $campaign = campaign(['status' => 'scheduled', 'scheduled_at' => now()->addHour()]);

    (new SendNewsletterBatch)->handle(app(CampaignSender::class));
    expect($campaign->fresh()->status)->toBe('scheduled');

    $this->travel(2)->hours();
    (new SendNewsletterBatch)->handle(app(CampaignSender::class));
    expect($campaign->fresh()->status)->toBe('sent');
    Mail::assertSent(CampaignMail::class, 2);
});

it('previews, test-sends and sends from the admin', function () {
    Mail::fake();
    Queue::fake();
    subscribers(confirmed: 3);
    $campaign = campaign();
    $this->actingAs(superAdmin(['email' => 'boss@example.com']));

    Livewire::test(EditCampaign::class, ['record' => $campaign->id])
        ->callAction('test', ['email' => 'boss@example.com'])
        ->callAction('send');

    Mail::assertSent(CampaignMail::class, fn ($m) => $m->test && $m->hasTo('boss@example.com'));
    expect($campaign->fresh())->status->toBe('sending')->recipients_count->toBe(3);
});

it('sends a lead magnet after the double opt-in is confirmed', function () {
    Mail::fake();
    Storage::fake('local');
    Storage::disk('local')->put('downloads/checklist.pdf', '%PDF checklist');
    app(SettingsRepository::class)->setMany(['features.newsletter' => true, 'newsletter.download' => 'downloads/checklist.pdf', 'newsletter.download_name' => 'Checklist.pdf', 'features.page_cache' => false]);
    $token = Str::random(48);
    Subscriber::create(['email' => 'new@example.com', 'token' => hash('sha256', $token), 'locale' => 'en']);

    $this->get(route('newsletter.confirm', $token))->assertRedirect('/')->assertSessionHas('download');
    $link = session('download');
    $this->get('/')->assertSee(e($link), false);

    Mail::assertQueued(Welcome::class, fn ($m) => $m->hasTo('new@example.com') && $m->download === $link);
    $this->get($link)->assertOk()->assertDownload('Checklist.pdf');
});
