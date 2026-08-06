<?php

namespace App\Models;

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
        'follower',
        'hotkey',
        'status',
        'dominant_color',
    ];

    protected function casts(): array
    {
        return [
            'status' => DetailStatus::class,
        ];
    }

    public function projectLive(): BelongsTo
    {
        return $this->belongsTo(ProjectLive::class);
    }

    public function imgUrl(): ?string
    {
        return $this->img ? Storage::disk('public')->url($this->img) : null;
    }
}
