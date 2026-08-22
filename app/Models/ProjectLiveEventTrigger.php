<?php

namespace App\Models;

use App\Enums\EventTriggerType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectLiveEventTrigger extends Model
{
    protected $fillable = [
        'project_live_id',
        'type',
        'mapped_gift_id',
        'command_text',
        'min_count',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'type' => EventTriggerType::class,
            'active' => 'boolean',
        ];
    }

    public function projectLive(): BelongsTo
    {
        return $this->belongsTo(ProjectLive::class);
    }

    public function mappedGift(): BelongsTo
    {
        return $this->belongsTo(TikTokGift::class, 'mapped_gift_id');
    }
}
