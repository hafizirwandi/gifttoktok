<?php

namespace App\Enums;

enum FrameOrientation: string
{
    case Portrait = 'portrait';
    case Landscape = 'landscape';
    case Square = 'square';

    public function label(): string
    {
        return match ($this) {
            self::Portrait => 'Portrait 9:16 (ukuran sama dengan Live Vertical)',
            self::Landscape => 'Landscape 16:9 (ukuran sama dengan Live Horizontal)',
            self::Square => 'Persegi 1:1 (ukuran sama dengan Layar Penuh Kotak)',
        };
    }

    /**
     * Rasio BAWAAN (preset) tiap orientasi - dipakai App\Livewire\ProjectLive\FrameHost
     * buat MENGISI project_lives.frame_ratio_w/frame_ratio_h saat tombol orientasi
     * diklik. Sesudah itu admin bebas ubah dua kolom itu manual ke angka lain
     * ("custom width/height") - jadi enum ini cuma quick-preset, BUKAN satu2nya
     * sumber rasio yang dipakai dirender (lihat frame-host.blade.php/
     * frame-host-live.blade.php yg selalu baca frame_ratio_w/h langsung, bukan
     * derive dari orientation).
     */
    public function ratioW(): int
    {
        return match ($this) {
            self::Portrait => 9,
            self::Landscape => 16,
            self::Square => 1,
        };
    }

    public function ratioH(): int
    {
        return match ($this) {
            self::Portrait => 16,
            self::Landscape => 9,
            self::Square => 1,
        };
    }
}
