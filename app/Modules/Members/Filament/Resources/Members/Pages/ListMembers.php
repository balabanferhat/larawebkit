<?php

namespace App\Modules\Members\Filament\Resources\Members\Pages;

use App\Modules\Members\Filament\Resources\Members\MemberResource;
use Filament\Resources\Pages\ListRecords;

class ListMembers extends ListRecords
{
    protected static string $resource = MemberResource::class;
}
