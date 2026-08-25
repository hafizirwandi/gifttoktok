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
     * Rasio + tinggi kontainer preview kursi (lihat preview-live.blade.php) — SAMA
     * PERSIS dgn aspect-[...] yang dipakai kontainer asli di live-show.blade.php, DAN
     * tingginya pakai satuan vh (bukan rem tetap) dgn proporsi yang SAMA PERSIS dgn
     * live-show.blade.php (Horizontal = separuh tinggi Vertical, sama seperti aslinya
     * h-screen/100vh vs h-[50vh]/50vh) — cuma di-skala 80% dari layar penuh (bukan
     * 100vh/50vh) krn ini nampil di dalam halaman admin, bukan fullscreen. Hasilnya
     * kotak kursi di preview ukurannya jadi sungguh-sungguh sebanding dgn kotak asli
     * di halaman Live pada layar yang sama, bukan cuma thumbnail kecil.
     */
    public function previewContainerClass(): string
    {
        return match ($this) {
            self::Vertical => 'h-[80vh] aspect-[1/2]',
            self::Horizontal => 'h-[40vh] aspect-[2/1]',
        };
    }

    /**
     * Susunan grid kursi di dalam kontainer preview — SAMA PERSIS dgn grid-cols/
     * grid-rows di live-show.blade.php.
     */
    public function previewGridClass(): string
    {
        return match ($this) {
            self::Vertical => 'grid-cols-2 grid-rows-4',
            self::Horizontal => 'grid-cols-4 grid-rows-2',
        };
    }
}
