<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectLiveColorHotkey extends Model
{
    protected $fillable = [
        'project_live_id',
        'hotkey',
        'color',
    ];

    public function projectLive(): BelongsTo
    {
        return $this->belongsTo(ProjectLive::class);
    }
}
