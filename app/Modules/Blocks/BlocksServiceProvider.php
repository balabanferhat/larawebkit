<?php

namespace App\Modules\Blocks;

use Illuminate\Support\ServiceProvider;

/**
 * Section blocks for pages. Dropping this module hides the "Sections" builder;
 * pages then render their editor content only (stored blocks are kept).
 */
class BlocksServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(BlockRegistry::class);
    }
}
