<?php

namespace App\Modules\Security\Filament\Resources\Users\Pages;

use App\Modules\Security\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}
