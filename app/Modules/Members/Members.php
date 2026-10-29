<?php

namespace App\Modules\Members;

use App\Modules\Core\ModuleRegistry;
use Illuminate\Support\Facades\Auth;

/** Small helpers other modules and views use without depending on the guard details. */
final class Members
{
    public static function enabled(): bool
    {
        return ModuleRegistry::enabled('members') && (bool) setting('features.members', false);
    }

    public static function current(): ?Models\Member
    {
        if (! ModuleRegistry::enabled('members')) {
            return null;
        }
        $member = Auth::guard('member')->user();

        return $member instanceof Models\Member ? $member : null;
    }

    /** Members-only content is visible to logged-in, verified members and to admins. */
    public static function canSeeMembersOnly(): bool
    {
        $member = self::current();

        return ($member && $member->hasVerifiedEmail() && $member->is_active) || Auth::guard('web')->check();
    }
}
