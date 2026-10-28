<?php

namespace App\Modules\Content\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

/** Fired after a page or post is saved (any status). SEO/GEO caches listen to it. */
class ContentSaved
{
    use Dispatchable;

    public function __construct(public readonly Model $content) {}
}
