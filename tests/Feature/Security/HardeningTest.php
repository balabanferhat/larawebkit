<?php

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::middleware(['web', 'public-form'])->post('/_test/form', fn () => response('accepted'))->name('test.form');
});

it('sends security headers', function () {
    $this->get('/admin/login')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
});

it('sends HSTS only over https', function () {
    $this->get('/admin/login')->assertHeaderMissing('Strict-Transport-Security');
    $this->get('https://localhost/admin/login')->assertHeader('Strict-Transport-Security');
});

it('silently drops honeypot submissions', function () {
    $this->withoutMiddleware(PreventRequestForgery::class)
        ->post('/_test/form', ['website_url' => 'spam', '_lwk_ts' => encrypt_ts(-10)])
        ->assertRedirect()->assertSessionHas('status');
});

it('drops forms submitted too fast', function () {
    $this->withoutMiddleware(PreventRequestForgery::class)
        ->post('/_test/form', ['_lwk_ts' => encrypt_ts(0)])
        ->assertRedirect();
});

it('accepts a genuine submission and rate limits per ip', function () {
    $self = $this->withoutMiddleware(PreventRequestForgery::class);
    for ($i = 0; $i < 5; $i++) {
        $self->post('/_test/form', ['_lwk_ts' => encrypt_ts(-5)])->assertOk()->assertSee('accepted');
    }
    $self->post('/_test/form', ['_lwk_ts' => encrypt_ts(-5)])->assertSessionHasErrors('email');
});

function encrypt_ts(int $offset): string
{
    return Crypt::encryptString((string) (time() + $offset));
}
