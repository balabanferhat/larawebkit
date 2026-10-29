<?php

namespace App\Modules\Forms;

use App\Modules\Forms\Models\Form;
use App\Modules\Forms\Models\FormSubmission;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** CSV export of a form's submissions (UTF-8 with BOM so Excel shows Turkish characters correctly). */
final class SubmissionExport
{
    public static function csv(Form $form, ?Builder $query = null): StreamedResponse
    {
        $query ??= $form->submissions()->getQuery();
        $fields = $form->fieldList();
        $filename = 'form-'.$form->slug.'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($query, $fields) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [__('larawebkit-forms.csv.date'), __('larawebkit-forms.csv.status'), ...array_map(fn ($f) => $f['label'] ?? $f['key'], $fields), __('larawebkit-forms.csv.notes')]);
            $query->reorder('id')->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $submission) {
                    /** @var FormSubmission $submission */
                    $values = array_map(fn ($v) => self::safe($v), array_values($submission->readable()));
                    fputcsv($out, [$submission->created_at?->toDateTimeString(), __('larawebkit-forms.status.'.$submission->status), ...$values, self::safe((string) $submission->notes)]);
                }
            });
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Neutralise spreadsheet formulas (CSV injection): =, +, -, @ at the start of a cell. */
    public static function safe(string $value): string
    {
        return preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }
}
