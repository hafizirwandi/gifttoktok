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
     * Tinggi+lebar kontainer preview kursi (lihat preview-live.blade.php), EKSPLISIT
     * dua-duanya (bukan h-[Xvh] aspect-[...]) — class aspect-[...] kombinasi dgn
     * elemen di dalam flex container ternyata bikin tingginya diam-diam menyusut
     * ngikutin konten (bukan beneran 92vh/46vh spt yang di-set), jadi kotak & tulisan
     * di dalamnya kepotong. Rasionya SAMA PERSIS dgn live-show.blade.php (1:2 potret
     * utk Vertical, 2:1 lanskap utk Horizontal, Horizontal = separuh tinggi Vertical
     * spt aslinya h-screen/100vh vs h-[50vh]/50vh), cuma disisakan sedikit dari 100vh
     * penuh (92vh) biar tidak mepet ke header halaman.
     */
    public function previewContainerClass(): string
    {
        return match ($this) {
            self::Vertical => 'h-[92vh] w-[46vh]',
            self::Horizontal => 'h-[46vh] w-[92vh]',
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
