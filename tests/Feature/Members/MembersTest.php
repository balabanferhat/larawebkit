<?php

use App\Modules\Content\Models\Post;
use App\Modules\Logs\Models\AuthLog;
use App\Modules\Members\Events\MemberDeleting;
use App\Modules\Members\Filament\Resources\Members\Pages\ListMembers;
use App\Modules\Members\Models\Member;
use App\Modules\Members\Notifications\MemberResetPassword;
use App\Modules\Members\Notifications\MemberVerifyEmail;
use App\Modules\Settings\SettingsRepository;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    app(SettingsRepository::class)->setMany(['features.members' => true, 'features.page_cache' => false]);
    $this->withoutMiddleware(PreventRequestForgery::class);
});

function member(array $attributes = []): Member
{
    $verified = array_key_exists('email_verified_at', $attributes) ? $attributes['email_verified_at'] : now();
    unset($attributes['email_verified_at']);
    $member = Member::create($attributes + ['name' => 'Reader', 'email' => 'reader@example.com', 'password' => 'correct-horse-battery']);
    $member->forceFill(['email_verified_at' => $verified])->save();

    return $member;
}

function secretPost(): Post
{
    return Post::factory()->published()->create([
        'title' => 'Members guide', 'slug' => 'members-guide', 'members_only' => true, 'excerpt' => '',
        'content' => '<p>SECRET-BODY-TEXT only for members, with enough words to matter for the description.</p>',
    ]);
}

it('is hidden while the feature is off', function () {
    app(SettingsRepository::class)->set('features.members', false);

    $this->get('/account/login')->assertNotFound();
    $this->get('/account/register')->assertNotFound();
});

it('registers a member, sends a verification link and confirms the address', function () {
    Notification::fake();

    $this->post('/account/register', ['_lwk_ts' => Crypt::encryptString((string) (time() - 10)), 'name' => 'Ada', 'email' => 'Ada@Example.com', 'password' => 'long-enough-password', 'password_confirmation' => 'long-enough-password', 'terms' => '1'])
        ->assertRedirect(route('members.verification.notice'));

    $member = Member::sole();
    expect($member->email)->toBe('ada@example.com')->and($member->hasVerifiedEmail())->toBeFalse();
    $this->assertAuthenticatedAs($member, 'member');
    $this->assertGuest('web');

    $url = null;
    Notification::assertSentTo($member, MemberVerifyEmail::class, function ($n) use ($member, &$url) {
        $url = $n->verificationUrl($member);

        return true;
    });
    $this->get($url)->assertRedirect(route('members.account'));
    expect($member->fresh()->hasVerifiedEmail())->toBeTrue();
    $this->get('/account')->assertOk()->assertSee('Ada');
});

it('requires the terms, a unique e-mail and a strong password', function () {
    member();

    $this->post('/account/register', ['_lwk_ts' => Crypt::encryptString((string) (time() - 10)), 'name' => 'X', 'email' => 'reader@example.com', 'password' => 'short', 'password_confirmation' => 'other'])
        ->assertSessionHasErrors(['email', 'password', 'terms']);
});

it('logs in, throttles wrong passwords and logs out', function () {
    $member = member();

    $this->post('/account/login', ['email' => 'reader@example.com', 'password' => 'wrong'])->assertSessionHasErrors('email');
    $this->post('/account/login', ['email' => 'READER@example.com', 'password' => 'correct-horse-battery'])->assertRedirect(route('members.account'));
    $this->assertAuthenticatedAs($member, 'member');
    expect($member->fresh()->last_login_at)->not->toBeNull()
        ->and(AuthLog::count())->toBe(0); // the admin auth log is for admin accounts only

    $this->post('/account/logout')->assertRedirect('/');
    $this->assertGuest('member');

    foreach (range(1, 6) as $i) {
        $response = $this->post('/account/login', ['email' => 'reader@example.com', 'password' => 'wrong-'.$i]);
    }
    $response->assertSessionHasErrors('email');
    expect(session('errors')->first('email'))->toContain('seconds');
});

it('does not let inactive members log in, and admins cannot use the member login', function () {
    member(['is_active' => false]);
    superAdmin(['email' => 'admin@example.com', 'password' => 'admin-password-123']);

    $this->post('/account/login', ['email' => 'reader@example.com', 'password' => 'correct-horse-battery'])->assertSessionHasErrors('email');
    $this->post('/account/login', ['email' => 'admin@example.com', 'password' => 'admin-password-123'])->assertSessionHasErrors('email');
});

it('resets a forgotten password without revealing which addresses exist', function () {
    Notification::fake();
    $member = member();

    $this->post('/account/forgot-password', ['email' => 'nobody@example.com'])->assertSessionHas('status');
    $this->post('/account/forgot-password', ['email' => 'reader@example.com'])->assertSessionHas('status');

    $token = null;
    Notification::assertSentTo($member, MemberResetPassword::class, function ($n) use (&$token) {
        $token = $n->token;

        return true;
    });
    $this->get(route('members.password.reset', ['token' => $token, 'email' => 'reader@example.com']))->assertOk();
    $this->post('/account/reset-password', ['token' => $token, 'email' => 'reader@example.com', 'password' => 'brand-new-password', 'password_confirmation' => 'brand-new-password'])
        ->assertRedirect(route('members.login'));

    $this->post('/account/login', ['email' => 'reader@example.com', 'password' => 'brand-new-password'])->assertRedirect(route('members.account'));
});

it('updates the profile and asks to confirm a new e-mail address', function () {
    Notification::fake();
    $member = member();
    $this->actingAs($member, 'member');

    $this->put('/account', ['name' => 'Renamed', 'email' => 'new@example.com'])->assertSessionHas('status');

    expect($member->fresh())->name->toBe('Renamed')->email->toBe('new@example.com')
        ->and($member->fresh()->hasVerifiedEmail())->toBeFalse();
    Notification::assertSentTo($member, MemberVerifyEmail::class);

    $this->put('/account/password', ['current_password' => 'wrong', 'password' => 'another-long-one', 'password_confirmation' => 'another-long-one'])->assertSessionHasErrors('current_password');
    $this->put('/account/password', ['current_password' => 'correct-horse-battery', 'password' => 'another-long-one', 'password_confirmation' => 'another-long-one'])->assertSessionHas('status');
});

it('deletes the account (GDPR / KVKK) after confirming the password', function () {
    Event::fake([MemberDeleting::class]);
    $member = member();
    $this->actingAs($member, 'member');

    $this->delete('/account', ['password' => 'wrong'])->assertSessionHasErrors('password');
    $this->delete('/account', ['password' => 'correct-horse-battery'])->assertRedirect('/');

    expect(Member::count())->toBe(0);
    Event::assertDispatched(MemberDeleting::class);
    $this->assertGuest('member');
});

it('keeps members-only text away from guests, feeds, the Markdown twin, llms-full.txt and search', function () {
    $post = secretPost();

    $html = $this->get('/blog/members-guide')->assertOk()->assertSee(__('larawebkit-members.gate.text'))->assertSee(route('members.login'), false)->getContent();
    expect($html)->not->toContain('SECRET-BODY-TEXT');
    $this->get('/blog/members-guide.md')->assertDontSee('SECRET-BODY-TEXT');
    $this->get('/feed.xml')->assertDontSee('SECRET-BODY-TEXT');
    $this->get('/llms-full.txt')->assertDontSee('SECRET-BODY-TEXT');
    $this->get('/search?q=SECRET-BODY')->assertDontSee('Members guide');
    $this->get('/blog')->assertSee('Members guide')->assertDontSee('SECRET-BODY-TEXT');
    expect($post->seoDescription())->not->toContain('SECRET');
});

it('shows members-only posts to verified members and admins', function () {
    secretPost();

    $this->actingAs(member(['email_verified_at' => null]), 'member');
    $this->get('/blog/members-guide')->assertDontSee('SECRET-BODY-TEXT')->assertSee(__('larawebkit-members.gate.verify'));

    $this->actingAs(member(['email' => 'ok@example.com']), 'member');
    $this->get('/blog/members-guide')->assertSee('SECRET-BODY-TEXT');

    auth('member')->logout();
    $this->actingAs(superAdmin(), 'web');
    $this->get('/blog/members-guide')->assertSee('SECRET-BODY-TEXT');
});

it('shows a login link in the header and lists members in the admin', function () {
    $this->get('/')->assertSee(route('members.login'), false);

    member();
    $this->actingAs(superAdmin());
    Livewire::test(ListMembers::class)->assertSee('reader@example.com');
});
