<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectLiveGiftEvent extends Model
{
    protected $fillable = [
        'project_live_id',
        'tiktok_user_id',
        'tiktok_gift_id',
        'repeat_count',
        'diamond_value',
    ];

    public function projectLive(): BelongsTo
    {
        return $this->belongsTo(ProjectLive::class);
    }

    public function gift(): BelongsTo
    {
        return $this->belongsTo(TikTokGift::class, 'tiktok_gift_id');
    }
}
