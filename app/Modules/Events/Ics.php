<?php

namespace App\Modules\Events;

use App\Modules\Events\Models\Event;
use App\Modules\Events\Models\Registration;
use App\Modules\Seo\CanonicalUrl;

/**
 * iCalendar (RFC 5545) file for one event. With a registration, the private online
 * link is included in the description and as the location of online events.
 */
final class Ics
{
    public static function make(Event $event, ?Registration $registration = null): string
    {
        $url = class_exists(CanonicalUrl::class) ? CanonicalUrl::for($event->publicPath()) : url($event->publicPath());
        $online = $registration && $event->isOnline() ? (string) $event->online_url : '';
        $description = trim(implode("\n\n", array_filter([
            (string) $event->summary,
            $online !== '' ? __('larawebkit-events.join_link').': '.$online : null,
            $url,
        ])));
        $location = $event->hasVenue() ? trim(implode(', ', array_filter([$event->venue_name, preg_replace('/\R+/', ', ', (string) $event->address)]))) : $online;

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//larawebkit//events//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:event-'.$event->id.'@'.(parse_url(url('/'), PHP_URL_HOST) ?: 'localhost'),
            'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
            'DTSTART:'.$event->starts_at->copy()->utc()->format('Ymd\THis\Z'),
            'DTEND:'.$event->endsOrDefault()->copy()->utc()->format('Ymd\THis\Z'),
            'SUMMARY:'.self::escape($event->title),
            'DESCRIPTION:'.self::escape($description),
            'URL:'.self::escape($url),
            ...($location !== '' ? ['LOCATION:'.self::escape($location)] : []),
            'STATUS:CONFIRMED',
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        return implode("\r\n", array_map([self::class, 'fold'], $lines))."\r\n";
    }

    public static function escape(string $value): string
    {
        return str_replace(['\\', ';', ',', "\r\n", "\n", "\r"], ['\\\\', '\;', '\,', '\n', '\n', '\n'], $value);
    }

    /** Lines longer than 75 octets continue on the next line with a leading space. */
    public static function fold(string $line): string
    {
        $out = '';
        while (strlen($line) > 75) {
            $cut = 75;
            while ($cut > 0 && (ord($line[$cut]) & 0xC0) === 0x80) {
                $cut--; // do not split a UTF-8 character
            }
            $out .= substr($line, 0, $cut)."\r\n ";
            $line = substr($line, $cut);
        }

        return $out.$line;
    }
}
