<?php

namespace App\Modules\Contact\Filament\Resources\ContactMessages\Pages;

use App\Modules\Contact\Filament\Resources\ContactMessages\ContactMessageResource;
use Filament\Resources\Pages\ListRecords;

class ListContactMessages extends ListRecords
{
    protected static string $resource = ContactMessageResource::class;
}
