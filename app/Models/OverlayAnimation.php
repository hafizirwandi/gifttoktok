<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

/**
 * Katalog MASTER animasi overlay (video WebM) - GLOBAL, dipakai bareng semua
 * project. Dipetakan ke gift asli lewat App\Models\TikTokGift::overlayAnimations()
 * (App\Livewire\ProjectLive\GiftMapping) atau ke Event Trigger lewat
 * App\Models\ProjectLiveEventTrigger::overlayAnimations() - keduanya lewat
 * App\Services\OverlayQueueService, diputar App\Livewire\ProjectLive\OverlayShow.
 * duration_mode 'auto' (default) = Show nunggu event "ended" bawaan <video>, 'manual'
 * = dipotong paksa di duration_ms.
 */
class OverlayAnimation extends Model
{
    protected $fillable = [
        'name',
        'file',
        'duration_ms',
        'duration_mode',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function fileUrl(): ?string
    {
        return $this->file ? Storage::disk('public')->url($this->file) : null;
    }

    public function tiktokGifts(): BelongsToMany
    {
        // Related pivot key dieksplisitkan - Eloquent menebak "tik_tok_gift_id" dari
        // nama class App\Models\TikTokGift (lihat komentar $table di model itu),
        // padahal kolom aslinya "tiktok_gift_id".
        return $this->belongsToMany(TikTokGift::class, 'tiktok_gift_overlay_animations', 'overlay_animation_id', 'tiktok_gift_id');
    }

    public function eventTriggers(): BelongsToMany
    {
        return $this->belongsToMany(ProjectLiveEventTrigger::class, 'project_live_event_trigger_overlay_animations');
    }
}
