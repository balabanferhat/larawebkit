<?php

namespace App\Modules\Ai\Filament\Resources\AiUsages\Pages;

use App\Modules\Ai\Filament\Resources\AiUsages\AiUsageResource;
use Filament\Resources\Pages\ListRecords;

class ListAiUsages extends ListRecords
{
    protected static string $resource = AiUsageResource::class;
}
