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
        return ['confirmed_at' => 'datetime', 'unsubscribed_at' => 'datetime'];
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
