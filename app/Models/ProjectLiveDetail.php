<?php

namespace App\Models;

use App\Enums\DetailSource;
use App\Enums\DetailStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProjectLiveDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_live_id',
        'position',
        'img',
        'name',
        'hotkey',
        'status',
        'dominant_color',
        'empty_label',
        'empty_icon',
        'active_hotkey_color',
        'source',
        'gift_total_value',
        'project_live_gifter_id',
        'last_gift_icon_url',
        'last_gift_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => DetailStatus::class,
            'source' => DetailSource::class,
            'last_gift_at' => 'datetime',
        ];
    }

    public function projectLive(): BelongsTo
    {
        return $this->belongsTo(ProjectLive::class);
    }

    public function gifter(): BelongsTo
    {
        return $this->belongsTo(ProjectLiveGifter::class, 'project_live_gifter_id');
    }

    public function imgUrl(): ?string
    {
        return $this->img ? Storage::disk('public')->url($this->img) : null;
    }
}
