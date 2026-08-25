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
     * Jumlah kolom grid preview kursi (lihat preview-live.blade.php) — beda per mode
     * supaya preview-nya BENERAN kelihatan berubah waktu tata letak diganti (2 kolom
     * "tinggi" utk Vertical, 4 kolom "lebar" utk Horizontal). Ukuran tiap kotak sendiri
     * DIBATASI via max-width di blade-nya (bukan lewat jumlah kolom), supaya Vertical
     * yang cuma 2 kolom tidak jadi kegedean dibanding Horizontal yang 4 kolom.
     */
    public function previewGridCols(): string
    {
        return match ($this) {
            self::Vertical => 'grid-cols-2',
            self::Horizontal => 'grid-cols-2 sm:grid-cols-4',
        };
    }
}
