<?php

use App\Modules\Newsletter\Mail\ConfirmSubscription;
use App\Modules\Newsletter\Models\Subscriber;
use App\Modules\Settings\SettingsRepository;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;

function formFields(): array
{
    return ['_lwk_ts' => Crypt::encryptString((string) (time() - 10))];
}

it('runs newsletter double opt-in', function () {
    Mail::fake();
    app(SettingsRepository::class)->set('features.newsletter', true);

    $this->withoutMiddleware(PreventRequestForgery::class)->post('/newsletter/subscribe', formFields() + ['email' => 'Reader@Example.com'])->assertSessionHas('status');

    $subscriber = Subscriber::sole();
    expect($subscriber->email)->toBe('reader@example.com')->and($subscriber->confirmed_at)->toBeNull();

    $token = null;
    Mail::assertQueued(ConfirmSubscription::class, function ($mail) use (&$token) {
        $token = $mail->token;

        return true;
    });
    expect($subscriber->token)->not->toBe($token); // stored hashed

    $this->get(route('newsletter.confirm', $token))->assertRedirect('/');
    expect($subscriber->fresh()->confirmed_at)->not->toBeNull();

    $this->get(route('newsletter.unsubscribe', $token));
    expect($subscriber->fresh()->unsubscribed_at)->not->toBeNull();
});

it('keeps the newsletter off by default', function () {
    $this->withoutMiddleware(PreventRequestForgery::class)->post('/newsletter/subscribe', formFields() + ['email' => 'a@example.com'])->assertNotFound();
    $this->get('/blog')->assertDontSee('newsletter/subscribe');
});

it('shows the cookie consent banner and loads analytics only after consent', function () {
    app(SettingsRepository::class)->set('analytics.google_analytics_id', 'G-TEST123');

    $html = $this->get('/blog')->assertOk()->assertSee('id="lwk-consent"', false)->getContent();
    expect($html)->not->toContain('<script async src="https://www.googletagmanager.com')
        ->and($html)->toContain('G-TEST123');
});
