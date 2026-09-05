<?php

namespace App\Models;

use App\Enums\BackgroundFit;
use App\Enums\BackgroundPlacement;
use App\Enums\BackgroundType;
use App\Enums\SeatRole;
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
        'role',
        'host_badge_bg_color',
        'host_badge_text_color',
        'host_badge_size',
    ];

    protected function casts(): array
    {
        return [
            'type' => BackgroundType::class,
            'placement' => BackgroundPlacement::class,
            'fit_mode' => BackgroundFit::class,
            'is_active' => 'boolean',
            'role' => SeatRole::class,
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

    /**
     * Bentuk array yang dipakai bareng oleh App\Livewire\ProjectLive\LiveShow DAN
     * App\Livewire\ProjectLive\PreviewLive (Preview render pakai partial seat-box.
     * blade.php yang SAMA PERSIS dgn Live, lihat komentar App\Models\ProjectLiveDetail::
     * toLiveArray()) - taruh di satu tempat ini biar keduanya tidak pernah beda bentuk.
     */
    public function toLiveArray(): array
    {
        return [
            'type' => $this->type->value,
            'url' => $this->fileUrl(),
            'fit_mode' => $this->fit_mode->value,
            'offset_x' => $this->offset_x,
            'offset_y' => $this->offset_y,
            'scale' => $this->scale,
            'role' => $this->role->value,
            'host_badge_bg_color' => $this->host_badge_bg_color,
            'host_badge_text_color' => $this->host_badge_text_color,
            'host_badge_size' => $this->host_badge_size,
        ];
    }
}
