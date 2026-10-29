<?php

use App\Modules\Core\LarawebkitServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;

return [
    AppServiceProvider::class,
    LarawebkitServiceProvider::class,
    AdminPanelProvider::class,
];
