<?php

namespace App\Enums;

enum BackgroundFit: string
{
    case Cover = 'cover';
    case Contain = 'contain';
    case Stretch = 'stretch';
    case Circle = 'circle';

    public function label(): string
    {
        return match ($this) {
            self::Cover => 'Cover (penuhi & potong kalau perlu)',
            self::Contain => 'Contain (utuh, mungkin ada ruang kosong)',
            self::Stretch => 'Stretch (paksa penuh, rasio bisa berubah)',
            self::Circle => 'Lingkaran (bulat di tengah kotak)',
        };
    }

    /**
     * Nilai CSS object-fit yang dipakai langsung di blade — 'stretch' bukan
     * keyword CSS object-fit asli, dipetakan ke 'fill' (istilah CSS-nya). 'circle'
     * TIDAK dirender lewat property ini sama sekali (butuh markup beda total, lihat
     * partials/seat-box.blade.php) - object-fit:cover di sini cuma dipakai media DI
     * DALAM lingkaran itu (biar tidak gepeng), bukan buat kotak penuh.
     */
    public function cssObjectFit(): string
    {
        return match ($this) {
            self::Cover => 'cover',
            self::Contain => 'contain',
            self::Stretch => 'fill',
            self::Circle => 'cover',
        };
    }

    public function isCircle(): bool
    {
        return $this === self::Circle;
    }
}
