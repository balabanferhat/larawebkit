<?php

use App\Modules\Logs\Http\Middleware\LogAccess;
use App\Modules\Logs\Models\AccessLog;
use App\Modules\Logs\Models\AuthLog;
use App\Modules\Logs\Support\AgentClassifier;
use App\Modules\Security\Filament\Auth\Login;
use App\Modules\Settings\SettingsRepository;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

it('logs successful and failed admin logins', function () {
    $user = editor(['email' => 'ed@example.com']);

    Livewire::test(Login::class)->fillForm(['email' => 'ed@example.com', 'password' => 'wrong'])->call('authenticate')->assertHasFormErrors();
    Livewire::test(Login::class)->fillForm(['email' => 'ed@example.com', 'password' => 'password'])->call('authenticate')->assertHasNoFormErrors();

    expect(AuthLog::pluck('event')->all())->toContain('failed', 'login')
        ->and($user->fresh()->last_login_at)->not->toBeNull();
});

it('logs password resets', function () {
    $user = editor();
    event(new PasswordReset($user));

    expect(AuthLog::where('event', 'password_reset')->where('user_id', $user->id)->exists())->toBeTrue();
});

it('logs a two-factor challenge when 2FA is enabled', function () {
    $user = editor(['email' => 'tfa@example.com']);
    $user->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');

    Livewire::test(Login::class)->fillForm(['email' => 'tfa@example.com', 'password' => 'password'])->call('authenticate');

    expect(AuthLog::where('event', 'two_factor_challenge')->exists())->toBeTrue()
        ->and(auth()->check())->toBeFalse();
});

it('classifies human, bot and AI bot user agents', function () {
    expect(AgentClassifier::classify('Mozilla/5.0 (Macintosh) Safari/605')['class'])->toBe('human')
        ->and(AgentClassifier::classify('Mozilla/5.0 (compatible; Googlebot/2.1)')['class'])->toBe('bot')
        ->and(AgentClassifier::classify('Mozilla/5.0 AppleWebKit/537.36 (compatible; GPTBot/1.2)'))->toBe(['class' => 'ai_bot', 'bot' => 'GPTBot'])
        ->and(AgentClassifier::classify('ClaudeBot/1.0')['bot'])->toBe('ClaudeBot');
});

it('logs front-end requests without storing the ip', function () {
    Route::middleware('web')->get('/_test/page', fn () => 'ok');

    $this->withHeaders(['User-Agent' => 'Mozilla/5.0 Chrome/120', 'Referer' => 'https://chatgpt.com/c/abc?x=1'])
        ->get('/_test/page')->assertOk();

    $log = AccessLog::sole();
    expect($log->path)->toBe('/_test/page')
        ->and($log->status)->toBe(200)
        ->and($log->agent_class)->toBe('human')
        ->and($log->referrer_host)->toBe('chatgpt.com')
        ->and($log->referrer)->toBe('https://chatgpt.com/c/abc')
        ->and($log->ip_hash)->toBe(LogAccess::hashIp('127.0.0.1'))
        ->and($log->ip_hash)->not->toContain('127.0.0.1');
});

it('does not log admin requests or when disabled', function () {
    $this->get('/admin/login');
    expect(AccessLog::count())->toBe(0);

    app(SettingsRepository::class)->set('features.access_log', false);
    Route::middleware('web')->get('/_test/page', fn () => 'ok');
    $this->get('/_test/page');
    expect(AccessLog::count())->toBe(0);
});

it('prunes old access logs according to the retention setting', function () {
    app(SettingsRepository::class)->set('privacy.access_log_retention_days', 30);
    $base = ['day' => now(), 'method' => 'GET', 'path' => '/', 'status' => 200, 'agent_class' => 'human'];
    AccessLog::create($base + ['created_at' => now()->subDays(40)]);
    AccessLog::create($base + ['created_at' => now()->subDays(5)]);

    $this->artisan('larawebkit:logs:prune')->assertSuccessful();

    expect(AccessLog::count())->toBe(1);
});
