<?php

namespace App\Modules\Security\Filament\Resources\Roles\Pages;

use App\Modules\Security\Filament\Resources\Roles\RoleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;
}
