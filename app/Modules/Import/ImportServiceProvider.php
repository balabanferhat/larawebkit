<?php

namespace App\Modules\Import;

use App\Modules\Import\Console\ImportWordPressCommand;
use Illuminate\Support\ServiceProvider;

/** Importers (WordPress). Console only; drop the module when a project does not need it. */
class ImportServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([ImportWordPressCommand::class]);
        }
    }
}
