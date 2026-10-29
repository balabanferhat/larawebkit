<?php

namespace App\Modules\Content\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

/** Fired once when a page or post becomes publicly visible (now or at its scheduled time). */
class ContentPublished
{
    use Dispatchable;

    public function __construct(public readonly Model $content) {}
}
