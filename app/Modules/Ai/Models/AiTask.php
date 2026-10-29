<?php

namespace App\Modules\Ai\Models;

use Illuminate\Database\Eloquent\Model;

/** A queued AI job (long drafts); the editor polls its status. */
class AiTask extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['input' => 'array'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
