<?php

namespace App\Modules\Members\Models;

use App\Modules\Members\Notifications\MemberResetPassword;
use App\Modules\Members\Notifications\MemberVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * A front-end member (reader, customer, event attendee). Completely separate from
 * admin users: own table, guard "member", password broker "members".
 */
class Member extends Authenticatable implements MustVerifyEmail
{
    use Notifiable;

    protected $fillable = ['name', 'email', 'password', 'locale', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

    protected $attributes = ['is_active' => true];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'last_login_at' => 'datetime', 'password' => 'hashed', 'is_active' => 'boolean'];
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new MemberVerifyEmail);
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new MemberResetPassword($token));
    }

    public function preferredLocale(): ?string
    {
        return $this->locale;
    }
}
