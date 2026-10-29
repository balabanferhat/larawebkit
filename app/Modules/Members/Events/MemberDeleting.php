<?php

namespace App\Modules\Members\Events;

use App\Modules\Members\Models\Member;
use Illuminate\Foundation\Events\Dispatchable;

/** Fired before a member deletes their account: modules remove or anonymise what belongs to them. */
class MemberDeleting
{
    use Dispatchable;

    public function __construct(public Member $member) {}
}
