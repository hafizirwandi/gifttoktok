<?php

namespace App\Models;

use App\Enums\DisplayMode;
use App\Enums\ProjectLiveStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectLive extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'status',
        'display_mode',
        'coin_size',
        'name_size',
        'avatar_size',
        'empty_icon_size',
        'empty_label_size',
        'gift_badge_size',
        'nama_akun',
        'desc',
        'user_id',
        'auto_gift_mode',
        'tiktok_username',
        'webhook_secret',
        'gift_listener_connected_at',
    ];

    protected $hidden = [
        'webhook_secret',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProjectLiveStatus::class,
            'display_mode' => DisplayMode::class,
            'auto_gift_mode' => 'boolean',
            'gift_listener_connected_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(ProjectLiveDetail::class)->orderBy('position');
    }

    public function gifters(): HasMany
    {
        return $this->hasMany(ProjectLiveGifter::class);
    }

    /**
     * Gift yang diaktifkan ("opt-in") untuk dihitung di leaderboard project ini.
     */
    public function enabledGifts(): BelongsToMany
    {
        return $this->belongsToMany(TikTokGift::class, 'project_live_gift_rules', 'project_live_id', 'tiktok_gift_id');
    }

    public function isLive(): bool
    {
        return $this->status === ProjectLiveStatus::Live;
    }

    /**
     * Dianggap "terhubung" kalau ada heartbeat dari service Node dalam 45 detik terakhir
     * (heartbeat dikirim tiap 20 detik selama service terhubung ke TikTok LIVE).
     */
    public function isGiftListenerOnline(): bool
    {
        return $this->gift_listener_connected_at !== null
            && $this->gift_listener_connected_at->gt(now()->subSeconds(45));
    }
}
