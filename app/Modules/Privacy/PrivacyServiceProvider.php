<?php

namespace App\Modules\Privacy;

use Illuminate\Support\ServiceProvider;

/**
 * Cookie consent (resources/views/components/lwk/consent.blade.php): no
 * analytics or marketing script runs before the visitor opts in. The
 * built-in page counter uses the cookieless access log instead.
 * Privacy and cookie policy page stubs are seeded by App\Modules\Content\DefaultContent.
 */
class PrivacyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        //
    }
}
