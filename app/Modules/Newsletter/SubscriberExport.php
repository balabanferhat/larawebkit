<?php

namespace App\Modules\Newsletter;

use App\Modules\Forms\SubmissionExport;
use App\Modules\Newsletter\Models\Subscriber;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** CSV of the subscribers with their consent record (UTF-8 with BOM, formula-safe). */
final class SubscriberExport
{
    public static function csv(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                __('larawebkit-newsletter.csv.email'), __('larawebkit-newsletter.csv.locale'), __('larawebkit-newsletter.csv.subscribed'),
                __('larawebkit-newsletter.csv.confirmed'), __('larawebkit-newsletter.csv.unsubscribed'),
                __('larawebkit-newsletter.consent.version'), __('larawebkit-newsletter.consent.at'), __('larawebkit-newsletter.consent.text'),
            ]);
            Subscriber::query()->orderBy('id')->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $s) {
                    fputcsv($out, [
                        self::safe($s->email), $s->locale, $s->created_at?->toDateTimeString(), $s->confirmed_at?->toDateTimeString(),
                        $s->unsubscribed_at?->toDateTimeString(), $s->consent_version, $s->consented_at?->toDateTimeString(), self::safe((string) $s->consent_text),
                    ]);
                }
            });
            fclose($out);
        }, 'newsletter-subscribers-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private static function safe(string $value): string
    {
        return class_exists(SubmissionExport::class) ? SubmissionExport::safe($value) : (preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value);
    }
}
