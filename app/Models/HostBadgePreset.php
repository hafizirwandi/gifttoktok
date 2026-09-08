<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Katalog MASTER (bukan per-project, sama pola dgn App\Models\TikTokGift) preset
 * warna/font tulisan badge "Host" - lihat komentar migrasi
 * create_host_badge_presets_table & App\Livewire\ProjectLive\PreviewLive::
 * applyHostBadgePreset(). Diisi lewat database/seeders/HostBadgePresetSeeder.php.
 */
class HostBadgePreset extends Model
{
    protected $fillable = [
        'name',
        'bg_color',
        'text_color',
        'font',
        'sort_order',
    ];
}
