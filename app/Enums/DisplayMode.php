<?php

namespace App\Enums;

enum DisplayMode: string
{
    case Vertical = 'vertical';
    case Horizontal = 'horizontal';

    public function label(): string
    {
        return match ($this) {
            self::Vertical => 'Vertical',
            self::Horizontal => 'Horizontal',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Vertical => 'Kursi memenuhi tinggi layar (2 kolom x 4 baris)',
            self::Horizontal => 'Kursi memenuhi lebar layar (4 kolom x 2 baris)',
        };
    }

    /**
     * Ikon diagram tata letak (lihat public/images/) — dipakai di tombol pilihan
     * DAN preview di detail-admin.blade.php, disimpan sbg file .svg biasa (bukan
     * inline) sesuai permintaan, warnanya sudah otomatis nyesuain dark/light lewat
     * @media (prefers-color-scheme) di dalam file svg-nya sendiri.
     */
    public function iconPath(): string
    {
        return match ($this) {
            self::Vertical => 'images/layout-vertical.svg',
            self::Horizontal => 'images/layout-horizontal.svg',
        };
    }

    /**
     * Class Tailwind buat grid daftar kursi di halaman Admin (detail-admin.blade.php)
     * — dibuat SAMA jumlah kolomnya dengan grid kursi asli di live-show.blade.php
     * (grid-cols-2 grid-rows-4 utk Vertical, grid-cols-4 grid-rows-2 utk Horizontal),
     * supaya admin langsung kebayang susunan aslinya sambil ngedit. 2 kolom tetap
     * dipakai di layar sempit (mobile) apa pun mode-nya, biar kotaknya tidak kekecilan.
     */
    public function adminGridClass(): string
    {
        return match ($this) {
            self::Vertical => 'grid-cols-2',
            self::Horizontal => 'grid-cols-2 sm:grid-cols-4',
        };
    }
}
