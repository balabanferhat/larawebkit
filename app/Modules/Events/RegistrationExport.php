<?php

namespace App\Modules\Events;

use App\Modules\Events\Models\Event;
use App\Modules\Forms\SubmissionExport;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** CSV of an event's registrations (UTF-8 with BOM, formula-safe). */
final class RegistrationExport
{
    public static function csv(Event $event): StreamedResponse
    {
        return response()->streamDownload(function () use ($event) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, array_map(fn ($k) => __("larawebkit-events.csv.$k"), ['name', 'email', 'status', 'member', 'registered_at', 'link_sent_at']));
            $event->registrations()->reorder('id')->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $r) {
                    fputcsv($out, [self::safe($r->name), self::safe($r->email), __('larawebkit-events.status.'.$r->status), $r->member_id ? __('larawebkit.yes') : '', $r->created_at?->toDateTimeString(), $r->link_sent_at?->toDateTimeString()]);
                }
            });
            fclose($out);
        }, 'event-'.$event->slug.'-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private static function safe(string $value): string
    {
        return class_exists(SubmissionExport::class) ? SubmissionExport::safe($value) : (preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value);
    }
}
