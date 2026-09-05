<?php

namespace App\Enums;

/**
 * Pilihan font teks kotak kosong (empty_label) per kursi - lihat
 * App\Livewire\ProjectLive\PreviewLive & partials/seat-box.blade.php. Daftar font
 * SENGAJA dikurasi (bukan input bebas) biar semuanya sudah pasti ke-load lewat
 * satu link Bunny Fonts di layouts/live.blade.php (lihat cssFontFamily() dipakai
 * bareng nama family di link itu - HARUS SAMA PERSIS kalau nambah case baru).
 */
enum SeatFont: string
{
    case Default = 'default';
    case Poppins = 'poppins';
    case Montserrat = 'montserrat';
    case PlayfairDisplay = 'playfair-display';
    case Oswald = 'oswald';
    case BebasNeue = 'bebas-neue';
    case Pacifico = 'pacifico';

    public function label(): string
    {
        return match ($this) {
            self::Default => 'Default',
            self::Poppins => 'Poppins',
            self::Montserrat => 'Montserrat',
            self::PlayfairDisplay => 'Playfair Display',
            self::Oswald => 'Oswald',
            self::BebasNeue => 'Bebas Neue',
            self::Pacifico => 'Pacifico',
        };
    }

    public function cssFontFamily(): string
    {
        return match ($this) {
            self::Default => "'Figtree', sans-serif",
            self::Poppins => "'Poppins', sans-serif",
            self::Montserrat => "'Montserrat', sans-serif",
            self::PlayfairDisplay => "'Playfair Display', serif",
            self::Oswald => "'Oswald', sans-serif",
            self::BebasNeue => "'Bebas Neue', cursive",
            self::Pacifico => "'Pacifico', cursive",
        };
    }
}
