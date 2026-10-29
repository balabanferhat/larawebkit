<?php

namespace App\Modules\Events\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;

class Registration extends Model
{
    protected $table = 'event_registrations';

    protected $guarded = ['id'];

    protected $attributes = ['status' => 'registered'];

    protected function casts(): array
    {
        return ['link_sent_at' => 'datetime'];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** Personal page with the online link, calendar file and cancel button (signed, works for guests). */
    public function url(): string
    {
        return URL::signedRoute('events.registration', ['event' => $this->event->slug, 'registration' => $this->id]);
    }

    public function icsUrl(): string
    {
        return URL::signedRoute('events.registration.ics', ['event' => $this->event->slug, 'registration' => $this->id]);
    }
}
