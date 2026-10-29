<?php

namespace App\Modules\Events\Models;

use App\Modules\Content\Support\HtmlSanitizer;
use App\Modules\Events\Events;
use App\Modules\Logs\Concerns\Auditable;
use App\Modules\Media\Models\Media;
use App\Modules\Seo\CanonicalUrl;
use App\Modules\Seo\Http\Controllers\SitemapController;
use App\Modules\Seo\Http\Middleware\PageCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * An event. Times are stored in UTC and shown in the event's timezone. The online
 * link is encrypted at rest and only shown to registrants.
 */
class Event extends Model
{
    use Auditable;

    public const LOCATION_TYPES = ['online', 'venue', 'hybrid'];

    protected $guarded = ['id'];

    protected $attributes = ['status' => 'draft', 'location_type' => 'online', 'registration_open' => true, 'timezone' => 'UTC'];

    protected array $auditExclude = ['online_url'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'capacity' => 'integer', 'registration_open' => 'boolean', 'online_url' => 'encrypted'];
    }

    protected static function booted(): void
    {
        static::saving(function (Event $event) {
            $event->slug = Str::slug($event->slug ?: $event->title) ?: Str::lower(Str::random(8));
            $event->locale = $event->locale ?: (string) setting('general.default_locale', 'en');
            if ($event->isDirty('description') && app()->bound(HtmlSanitizer::class)) {
                $event->description = app(HtmlSanitizer::class)->sanitize($event->description);
            }
        });
        $flush = function () {
            if (class_exists(PageCache::class)) {
                PageCache::bust();
                SitemapController::flush();
            }
        };
        static::saved($flush);
        static::deleted($flush);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function featuredMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'featured_media_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    /** Published and cancelled events have a public page (a cancelled event says so). */
    public function scopeListed(Builder $query): Builder
    {
        return $query->whereIn('status', ['published', 'cancelled']);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where(fn ($q) => $q->where('ends_at', '>=', now())->orWhere(fn ($w) => $w->whereNull('ends_at')->where('starts_at', '>=', now()->subHours(3))));
    }

    public function localStart(): Carbon
    {
        return $this->starts_at->copy()->setTimezone($this->timezone ?: 'UTC');
    }

    public function localEnd(): ?Carbon
    {
        return $this->ends_at?->copy()->setTimezone($this->timezone ?: 'UTC');
    }

    public function endsOrDefault(): Carbon
    {
        return $this->ends_at ?? $this->starts_at->copy()->addHour();
    }

    public function isPast(): bool
    {
        return $this->endsOrDefault()->isPast();
    }

    public function registeredCount(): int
    {
        return $this->registrations()->where('status', 'registered')->count();
    }

    public function spotsLeft(): ?int
    {
        return $this->capacity ? max(0, $this->capacity - $this->registeredCount()) : null;
    }

    public function isFull(): bool
    {
        return $this->capacity !== null && $this->spotsLeft() === 0;
    }

    public function canRegister(): bool
    {
        return $this->status === 'published' && $this->registration_open && ! $this->isPast() && ! $this->isFull();
    }

    public function isOnline(): bool
    {
        return in_array($this->location_type, ['online', 'hybrid'], true);
    }

    public function hasVenue(): bool
    {
        return in_array($this->location_type, ['venue', 'hybrid'], true);
    }

    public function publicPath(): string
    {
        return localized_path('/'.Events::path($this->locale).'/'.$this->slug, $this->locale);
    }

    /**
     * schema.org Event. The private online link is never included: the virtual location
     * points at the public event page.
     *
     * @return array<string, mixed>
     */
    public function schema(): array
    {
        $url = CanonicalUrl::for($this->publicPath());
        $virtual = ['@type' => 'VirtualLocation', 'url' => $url];
        $place = array_filter(['@type' => 'Place', 'name' => $this->venue_name ?: null, 'address' => $this->address ? preg_replace('/\R+/', ', ', $this->address) : null]);
        $location = match ($this->location_type) {
            'venue' => $place,
            'hybrid' => [$place, $virtual],
            default => $virtual,
        };

        return array_filter([
            '@type' => 'Event',
            '@id' => $url.'#event',
            'name' => $this->title,
            'description' => $this->summary ?: null,
            'url' => $url,
            'inLanguage' => $this->locale,
            'startDate' => $this->localStart()->toIso8601String(),
            'endDate' => $this->endsOrDefault()->copy()->setTimezone($this->timezone ?: 'UTC')->toIso8601String(),
            'eventStatus' => 'https://schema.org/'.($this->status === 'cancelled' ? 'EventCancelled' : 'EventScheduled'),
            'eventAttendanceMode' => 'https://schema.org/'.['online' => 'OnlineEventAttendanceMode', 'venue' => 'OfflineEventAttendanceMode', 'hybrid' => 'MixedEventAttendanceMode'][$this->location_type],
            'location' => $location,
            'image' => $this->featuredMedia ? [$this->featuredMedia->url(1280)] : null,
            'organizer' => ['@id' => CanonicalUrl::for('/').'#organization'],
            'maximumAttendeeCapacity' => $this->capacity ?: null,
            'remainingAttendeeCapacity' => $this->capacity ? $this->spotsLeft() : null,
            'offers' => [
                '@type' => 'Offer',
                'price' => 0,
                'priceCurrency' => (string) config('larawebkit-events.currency', 'USD'),
                'url' => $url,
                'availability' => 'https://schema.org/'.($this->isFull() ? 'SoldOut' : 'InStock'),
                'validFrom' => $this->created_at?->toIso8601String(),
            ],
            'isAccessibleForFree' => true,
        ], fn ($v) => $v !== null);
    }

    public function auditLabel(): string
    {
        return $this->title;
    }
}
