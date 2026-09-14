<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TikTokGift extends Model
{
    // Eloquent menebak nama tabel "tik_tok_gifts" (memisah Tik+Tok) — nama tabel sebenarnya "tiktok_gifts".
    protected $table = 'tiktok_gifts';

    protected $fillable = [
        'tiktok_gift_id',
        'name',
        'diamond_count',
        'icon_url',
        'is_custom',
    ];

    protected function casts(): array
    {
        return [
            'is_custom' => 'boolean',
        ];
    }

    public function projectLives(): BelongsToMany
    {
        return $this->belongsToMany(ProjectLive::class, 'project_live_gift_rules', 'tiktok_gift_id', 'project_live_id');
    }

    /**
     * Gift lain yang "wajahnya" BOLEH dipakai gift ini kalau dikirim (lihat
     * GiftMapping) - boleh lebih dari satu, salah satunya dipilih ACAK tiap kali
     * gift ini diterima (App\Services\TikTokGiftEventProcessor::stampGiftIcon()).
     * Dulu kolom tunggal mapped_to_gift_id, sekarang pivot (lihat migration ganti-nya).
     */
    public function mappedTargets(): BelongsToMany
    {
        // Self-referencing (dua-duanya sama-sama nunjuk ke tiktok_gifts) - foreign/
        // related pivot key WAJIB dieksplisitkan, tidak ada nama kolom "tebakan"
        // yang benar buat kasus ini (beda dari overlayAnimations() yang cuma perlu
        // benerin SATU sisi krn tabel relasinya beda).
        return $this->belongsToMany(self::class, 'tiktok_gift_mapped_targets', 'source_gift_id', 'target_gift_id');
    }

    /**
     * Animasi overlay yang diputar tiap kali gift ini BENERAN diterima (App\Services\
     * TikTokGiftEventProcessor::applyGift(), cuma $isRealGiftEvent=true) - boleh
     * lebih dari satu, salah satunya dipilih ACAK (App\Services\OverlayQueueService).
     * Diatur lewat halaman "Pemetaan Gift" (App\Livewire\ProjectLive\GiftMapping).
     */
    public function overlayAnimations(): BelongsToMany
    {
        // Foreign pivot key dieksplisitkan - Eloquent menebak "tik_tok_gift_id" dari
        // nama class (sama gotcha-nya dgn nama tabel, lihat komentar $table di atas),
        // padahal kolom aslinya "tiktok_gift_id".
        return $this->belongsToMany(OverlayAnimation::class, 'tiktok_gift_overlay_animations', 'tiktok_gift_id', 'overlay_animation_id');
    }
}
