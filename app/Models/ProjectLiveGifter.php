<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProjectLiveGifter extends Model
{
    protected $fillable = [
        'project_live_id',
        'tiktok_user_id',
        'tiktok_unique_id',
        'nickname',
        'avatar_path',
        'dominant_color',
        'total_value',
        'round_value',
        'gift_count',
        'last_gift_at',
    ];

    protected function casts(): array
    {
        return [
            'last_gift_at' => 'datetime',
        ];
    }

    public function projectLive(): BelongsTo
    {
        return $this->belongsTo(ProjectLive::class);
    }

    public function avatarUrl(): ?string
    {
        return $this->avatar_path ? Storage::disk('public')->url($this->avatar_path) : null;
    }
}
