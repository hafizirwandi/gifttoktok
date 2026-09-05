<?php

namespace App\Enums;

/**
 * Peran tambahan utk kursi yang jadi BG (App\Livewire\ProjectLive\PreviewLive::
 * saveBgEdit()) - cuma relevan kalau placement=seat, lihat App\Models\
 * ProjectLiveBackground.
 */
enum SeatRole: string
{
    case None = 'none';
    case Host = 'host';
    case CoHost = 'co_host';

    public function label(): string
    {
        return match ($this) {
            self::None => 'Biasa (cuma media BG)',
            self::Host => 'Host (tambah badge "Host")',
            self::CoHost => 'Co-Host (jadi spt kursi normal - nama/coin/mic)',
        };
    }
}
