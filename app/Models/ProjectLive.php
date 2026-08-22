<?php

namespace App\Models;

use App\Enums\DisplayMode;
use App\Enums\FrameOrientation;
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
        'mic_size',
        'frame_orientation',
        'frame_color',
        'frame_radius',
        'frame_border_width',
        'frame_visible',
        'frame_pulse',
        'frame_pulse_speed_ms',
        'frame_pulse_color_1',
        'frame_pulse_color_2',
        'frame_pulse_color_3',
        'active_hotkey_color',
        'default_color_hotkey',
        'reset_leaderboard_hotkey',
        'reset_coin_hotkey',
        'nama_akun',
        'desc',
        'user_id',
        'auto_gift_mode',
        'tiktok_username',
        'webhook_secret',
        'gift_listener_connected_at',
        'round_reset_at',
    ];

    protected $hidden = [
        'webhook_secret',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProjectLiveStatus::class,
            'display_mode' => DisplayMode::class,
            'frame_orientation' => FrameOrientation::class,
            'frame_visible' => 'boolean',
            'frame_pulse' => 'boolean',
            'auto_gift_mode' => 'boolean',
            'gift_listener_connected_at' => 'datetime',
            'round_reset_at' => 'datetime',
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
     * Daftar hotkey warna global (lihat App\Livewire\ProjectLive\HotkeyColor).
     */
    public function colorHotkeys(): HasMany
    {
        return $this->hasMany(ProjectLiveColorHotkey::class);
    }

    /**
     * Trigger event non-gift (join/share/follow/subscribe/like/chat) — lihat
     * App\Services\EventTriggerProcessor.
     */
    public function eventTriggers(): HasMany
    {
        return $this->hasMany(ProjectLiveEventTrigger::class);
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

    /**
     * Semua hotkey char di project ini berbagi SATU keydown handler di halaman Live
     * (lihat live-show.blade.php) — hotkey reveal-kursi, hotkey warna (global/per-kursi),
     * hotkey default warna, hotkey Reset Leaderboard, dan hotkey Reset Coin. Dipakai
     * SATU tempat ini oleh semua fitur yang punya field hotkey (DetailAdmin::save(),
     * HotkeyColor::saveHotkey()/saveDefaultHotkey(), reset hotkey di DetailAdmin) supaya
     * validasi tabrakan konsisten & tidak diam-diam menang begitu ada 2 fitur pakai
     * huruf yang sama.
     *
     * @param  string  $hotkey  Huruf/angka yang mau dicek (case-insensitive).
     * @param  string|null  $exclude  Sumber yang boleh diabaikan (dirinya sendiri saat
     *                                sedang di-edit): "seat:{id}"|"color:{id}"|"default"|
     *                                "reset_leaderboard"|"reset_coin".
     * @return string|null  Label fitur yang sudah pakai hotkey ini, null kalau bebas.
     */
    public function findHotkeyConflict(string $hotkey, ?string $exclude = null): ?string
    {
        $hotkey = strtolower($hotkey);

        if ($exclude !== 'default' && $this->default_color_hotkey === $hotkey) {
            return 'hotkey default warna';
        }

        if ($exclude !== 'reset_leaderboard' && $this->reset_leaderboard_hotkey === $hotkey) {
            return 'hotkey Reset Leaderboard';
        }

        if ($exclude !== 'reset_coin' && $this->reset_coin_hotkey === $hotkey) {
            return 'hotkey Reset Coin';
        }

        $colorHotkeyQuery = $this->colorHotkeys()->where('hotkey', $hotkey);

        if (str_starts_with((string) $exclude, 'color:')) {
            $colorHotkeyQuery->whereKeyNot((int) substr($exclude, 6));
        }

        if ($colorHotkeyQuery->exists()) {
            return 'hotkey warna';
        }

        $seatQuery = $this->details()->where('hotkey', $hotkey);

        if (str_starts_with((string) $exclude, 'seat:')) {
            $seatQuery->whereKeyNot((int) substr($exclude, 5));
        }

        $seat = $seatQuery->first();

        if ($seat) {
            return 'hotkey kursi'.($seat->name ? " \"{$seat->name}\"" : '');
        }

        return null;
    }
}
