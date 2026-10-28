<?php

namespace App\Modules\Content\Filament\Resources\Tags\Pages;

use App\Modules\Content\Filament\Resources\Tags\TagResource;
use Filament\Resources\Pages\ManageRecords;

class ManageTags extends ManageRecords
{
    protected static string $resource = TagResource::class;
}
