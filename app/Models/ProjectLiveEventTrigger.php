<?php

namespace App\Models;

use App\Enums\EventTriggerType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProjectLiveEventTrigger extends Model
{
    protected $fillable = [
        'project_live_id',
        'type',
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

    /**
     * Gift-gift yang BOLEH "muncul" kalau trigger ini kena - boleh lebih dari satu,
     * salah satunya dipilih ACAK tiap kali trigger-nya jalan (lihat
     * App\Services\EventTriggerProcessor::handle()). Dulu kolom tunggal
     * mapped_gift_id, sekarang pivot (lihat migration ganti-nya).
     */
    public function mappedGifts(): BelongsToMany
    {
        // Related pivot key dieksplisitkan - Eloquent menebak "tik_tok_gift_id" dari
        // nama class App\Models\TikTokGift, padahal kolom aslinya "tiktok_gift_id"
        // (lihat komentar $table di model itu).
        return $this->belongsToMany(TikTokGift::class, 'project_live_event_trigger_gifts', 'project_live_event_trigger_id', 'tiktok_gift_id');
    }

    /**
     * Animasi overlay pilihan trigger ini - opsional, boleh lebih dari satu, salah
     * satunya dipilih ACAK (App\Services\OverlayQueueService::enqueueRandom()).
     */
    public function overlayAnimations(): BelongsToMany
    {
        return $this->belongsToMany(OverlayAnimation::class, 'project_live_event_trigger_overlay_animations');
    }
}
