<?php

namespace App\Modules\Events\Listeners;

use App\Modules\Events\Models\Registration;
use App\Modules\Members\Events\MemberDeleting;

/** A deleted account takes its event registrations with it (GDPR / KVKK). */
class ForgetMemberRegistrations
{
    public function handle(MemberDeleting $event): void
    {
        Registration::where('member_id', $event->member->id)->orWhere('email', $event->member->email)->delete();
    }
}
