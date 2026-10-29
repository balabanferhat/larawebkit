<?php

namespace App\Modules\Forms\Filament\Resources\FormSubmissions\Pages;

use App\Modules\Forms\Filament\Resources\FormSubmissions\FormSubmissionResource;
use Filament\Resources\Pages\ListRecords;

class ListFormSubmissions extends ListRecords
{
    protected static string $resource = FormSubmissionResource::class;
}
