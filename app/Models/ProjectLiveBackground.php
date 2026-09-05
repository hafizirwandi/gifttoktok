<?php

namespace App\Models;

use App\Enums\BackgroundFit;
use App\Enums\BackgroundPlacement;
use App\Enums\BackgroundType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProjectLiveBackground extends Model
{
    protected $fillable = [
        'project_live_id',
        'name',
        'type',
        'placement',
        'seat_position',
        'file',
        'fit_mode',
        'offset_x',
        'offset_y',
        'scale',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => BackgroundType::class,
            'placement' => BackgroundPlacement::class,
            'fit_mode' => BackgroundFit::class,
            'is_active' => 'boolean',
        ];
    }

    public function projectLive(): BelongsTo
    {
        return $this->belongsTo(ProjectLive::class);
    }

    public function fileUrl(): ?string
    {
        return $this->file ? Storage::disk('public')->url($this->file) : null;
    }
}
