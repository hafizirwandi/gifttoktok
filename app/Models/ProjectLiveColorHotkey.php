<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectLiveColorHotkey extends Model
{
    protected $fillable = [
        'project_live_id',
        'project_live_detail_id',
        'hotkey',
        'color',
    ];

    public function projectLive(): BelongsTo
    {
        return $this->belongsTo(ProjectLive::class);
    }

    /**
     * Null berarti hotkey ini GLOBAL (semua kotak kosong ikut berubah warna).
     */
    public function detail(): BelongsTo
    {
        return $this->belongsTo(ProjectLiveDetail::class, 'project_live_detail_id');
    }
}
