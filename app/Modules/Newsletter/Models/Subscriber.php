<?php

namespace App\Modules\Newsletter\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

class Subscriber extends Model
{
    protected $table = 'newsletter_subscribers';

    protected $guarded = ['id'];

    protected $hidden = ['token'];

    protected function casts(): array
    {
        return ['confirmed_at' => 'datetime', 'unsubscribed_at' => 'datetime', 'consented_at' => 'datetime', 'consent_version' => 'integer'];
    }

    /** The consent sentence shown next to the checkbox (setting, or the translated default). */
    public static function consentText(): string
    {
        return trim((string) setting('newsletter.consent_text', '')) ?: __('larawebkit-front.newsletter.consent');
    }

    /** Permanent signed link for campaign e-mails (also the RFC 8058 one-click target). */
    public function unsubscribeUrl(): string
    {
        return URL::signedRoute('newsletter.unsubscribe.signed', ['subscriber' => $this->id]);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotNull('confirmed_at')->whereNull('unsubscribed_at');
    }
}
