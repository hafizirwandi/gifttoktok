<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris antrian animasi overlay (App\Services\OverlayQueueService) - status
 * pending -> playing -> done, diproses App\Livewire\ProjectLive\OverlayShow lewat
 * polling (bukan broadcast/websocket).
 */
class ProjectLiveOverlayQueueItem extends Model
{
    protected $fillable = [
        'project_live_id',
        'overlay_animation_id',
        'status',
        'played_at',
    ];

    protected function casts(): array
    {
        return [
            'played_at' => 'datetime',
        ];
    }

    public function projectLive(): BelongsTo
    {
        return $this->belongsTo(ProjectLive::class);
    }

    public function overlayAnimation(): BelongsTo
    {
        return $this->belongsTo(OverlayAnimation::class);
    }
}
