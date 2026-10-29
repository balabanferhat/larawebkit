<?php

namespace App\Modules\Content\Filament\Resources\Categories\Pages;

use App\Modules\Content\Filament\Resources\Categories\CategoryResource;
use Filament\Resources\Pages\ManageRecords;

class ManageCategories extends ManageRecords
{
    protected static string $resource = CategoryResource::class;
}
