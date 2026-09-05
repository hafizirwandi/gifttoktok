<?php

namespace App\Enums;

enum BackgroundFit: string
{
    case Cover = 'cover';
    case Contain = 'contain';
    case Stretch = 'stretch';

    public function label(): string
    {
        return match ($this) {
            self::Cover => 'Cover (penuhi & potong kalau perlu)',
            self::Contain => 'Contain (utuh, mungkin ada ruang kosong)',
            self::Stretch => 'Stretch (paksa penuh, rasio bisa berubah)',
        };
    }

    /**
     * Nilai CSS object-fit yang dipakai langsung di blade — 'stretch' bukan
     * keyword CSS object-fit asli, dipetakan ke 'fill' (istilah CSS-nya).
     */
    public function cssObjectFit(): string
    {
        return match ($this) {
            self::Cover => 'cover',
            self::Contain => 'contain',
            self::Stretch => 'fill',
        };
    }
}
