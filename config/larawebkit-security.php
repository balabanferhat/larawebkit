<?php

return [

    // Require every admin to set up TOTP two-factor authentication before using the panel.
    'require_two_factor' => (bool) env('ADMIN_REQUIRE_2FA', false),

    // Sent on every response by App\Modules\Security\Http\Middleware\SecurityHeaders.
    'headers' => [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'SAMEORIGIN',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), interest-cohort=()',
        'Cross-Origin-Opener-Policy' => 'same-origin',
    ],

    // HSTS is only sent over HTTPS. Set to 0 to disable.
    'hsts_max_age' => (int) env('SECURITY_HSTS_MAX_AGE', 31536000),

    // Optional Content-Security-Policy for the public site (not the admin, which needs inline Alpine).
    'front_csp' => env('SECURITY_FRONT_CSP'),

    'honeypot' => [
        'field' => 'website_url',
        'time_field' => '_lwk_ts',
        'min_seconds' => 2,
    ],

    // Public form submissions per minute per IP (contact, newsletter).
    'public_form_rate_limit' => (int) env('PUBLIC_FORM_RATE_LIMIT', 5),

];
