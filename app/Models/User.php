<?php

namespace App\Models;

use App\Modules\Logs\Concerns\Auditable;
use App\Modules\Logs\Models\AuthLog;
use App\Modules\Media\Models\Media;
use Database\Factories\UserFactory;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

/**
 * Admin account (editors, authors, super admins). Front-end members are a
 * separate model planned for v0.2, so a public sign-up can never reach /admin.
 */
class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery
{
    /** @use HasFactory<UserFactory> */
    use Auditable, HasFactory, HasRoles, InteractsWithAppAuthentication, InteractsWithAppAuthenticationRecovery, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'is_active', 'slug', 'job_title', 'bio', 'expertise', 'avatar_media_id', 'same_as', 'locale',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $attributes = ['is_active' => true];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'same_as' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (User $user) {
            if (blank($user->slug) && filled($user->name)) {
                $base = Str::slug($user->name) ?: 'author';
                $slug = $base;
                $i = 2;
                while (static::where('slug', $slug)->whereKeyNot($user->getKey())->exists()) {
                    $slug = $base.'-'.$i++;
                }
                $user->slug = $slug;
            }
        });

        static::updated(function (User $user) {
            if ($user->wasChanged('app_authentication_secret')) {
                AuthLog::record(filled($user->app_authentication_secret) ? 'two_factor_enabled' : 'two_factor_disabled', $user->id, $user->email);
            }
        });
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && $this->roles()->exists();
    }

    public function avatar(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'avatar_media_id');
    }

    public function auditLabel(): string
    {
        return $this->name.' <'.$this->email.'>';
    }

    public function hasTwoFactor(): bool
    {
        return filled($this->app_authentication_secret);
    }
}
