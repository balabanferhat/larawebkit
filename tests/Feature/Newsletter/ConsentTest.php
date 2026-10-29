<?php

use App\Modules\Content\Models\Page;
use App\Modules\Newsletter\Filament\Resources\Subscribers\Pages\ListSubscribers;
use App\Modules\Newsletter\Models\Subscriber;
use App\Modules\Newsletter\SubscriberExport;
use App\Modules\Settings\Filament\Pages\ManageSettings;
use App\Modules\Settings\SettingsRepository;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    Mail::fake();
    app(SettingsRepository::class)->setMany(['features.newsletter' => true, 'features.page_cache' => false]);
    $this->withoutMiddleware(PreventRequestForgery::class);
});

function subscribe(array $data)
{
    return test()->from('/')->post('/newsletter/subscribe', ['_lwk_ts' => Crypt::encryptString((string) (time() - 10))] + $data);
}

it('needs no checkbox by default', function () {
    $this->get('/')->assertDontSee('name="consent"', false);
    subscribe(['email' => 'a@example.com'])->assertSessionHas('status');

    expect(Subscriber::sole())->consent_version->toBeNull()->consented_at->toBeNull();
});

it('asks for an unticked consent checkbox and records the accepted text and version', function () {
    app(SettingsRepository::class)->setMany(['newsletter.require_consent' => true, 'newsletter.consent_text' => 'Yes, send me the letters.', 'newsletter.consent_version' => 3]);

    $html = $this->get('/')->assertSee('Yes, send me the letters.')->assertSee('/privacy-policy', false)->getContent();
    expect($html)->toMatch('/<input type="checkbox" name="consent" value="1" required>/');

    subscribe(['email' => 'a@example.com'])->assertSessionHasErrors('consent');
    expect(Subscriber::count())->toBe(0);

    subscribe(['email' => 'a@example.com', 'consent' => '1'])->assertSessionHas('status');
    expect(Subscriber::sole())->consent_version->toBe(3)->consent_text->toBe('Yes, send me the letters.')->consented_at->not->toBeNull();

    ob_start();
    SubscriberExport::csv()->sendContent();
    expect((string) ob_get_clean())->toContain('a@example.com')->toContain('Yes, send me the letters.')->toContain(',3,');

    $this->actingAs(superAdmin());
    Livewire::test(ListSubscribers::class)->assertSee('a@example.com')->callAction('export')->assertFileDownloaded();
});

it('uses the sign-up box text from the settings, and the personal theme promises no schedule', function () {
    app(SettingsRepository::class)->set('general.theme', 'personal');
    $html = $this->get('/blog')->getContent(); // the band is not shown on the home page
    expect($html)->not->toContain('every other Sunday')->toContain(__('theme-personal::theme.newsletter_text'));

    app(SettingsRepository::class)->setMany(['newsletter.band_title' => 'Birtane mektupları', 'newsletter.band_text' => 'Ayda bir mektup.']);
    $this->get('/blog')->assertSee('Birtane mektupları')->assertSee('Ayda bir mektup.');
});

it('links the consent text to the page chosen in the settings, in the visitor language', function () {
    app(SettingsRepository::class)->set('newsletter.require_consent', true);
    $this->get('/')->assertSee('href="/privacy-policy"', false); // default unchanged

    $notice = Page::factory()->published()->create(['title' => 'Data protection notice', 'slug' => 'data-notice', 'locale' => 'en']);
    app(SettingsRepository::class)->set('newsletter.consent_page', (string) $notice->id);

    $this->get('/')->assertSee('href="/data-notice"', false)->assertDontSee('href="/privacy-policy">'.__('larawebkit-front.newsletter.privacy'), false);
    expect(Subscriber::consentUrl())->toBe('/data-notice');

    // A translation of the chosen page is used in its language; a deleted page falls back to the privacy policy.
    app(SettingsRepository::class)->set('general.active_locales', ['en', 'tr']);
    Page::factory()->published()->create(['title' => 'Aydınlatma metni', 'slug' => 'aydinlatma-metni', 'locale' => 'tr', 'translation_group' => $notice->translation_group]);
    expect(Subscriber::consentUrl('tr'))->toBe('/tr/aydinlatma-metni');
    $notice->delete();
    expect(Subscriber::consentUrl('en'))->toBe('/privacy-policy');

    $this->actingAs(superAdmin());
    Livewire::test(ManageSettings::class)->assertSee('Aydınlatma metni (tr)');
});
