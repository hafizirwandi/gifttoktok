<?php

namespace App\Enums;

enum DisplayMode: string
{
    case Vertical = 'vertical';
    case Horizontal = 'horizontal';
    case FullPortrait = 'full_portrait';
    case FullSquare = 'full_square';
    case FullLandscape = 'full_landscape';
    case Grid2x2 = 'grid_2x2';
    case Grid3x3 = 'grid_3x3';
    case Grid4x4 = 'grid_4x4';

    public function label(): string
    {
        return match ($this) {
            self::Vertical => 'Vertical',
            self::Horizontal => 'Horizontal',
            self::FullPortrait => 'Layar Penuh (Potret)',
            self::FullSquare => 'Layar Penuh (Kotak)',
            self::FullLandscape => 'Layar Penuh (Lanskap)',
            self::Grid2x2 => '2x2',
            self::Grid3x3 => '3x3',
            self::Grid4x4 => '4x4',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Vertical => 'Kursi memenuhi tinggi layar (2 kolom x 4 baris)',
            self::Horizontal => 'Kursi memenuhi lebar layar (4 kolom x 2 baris)',
            self::FullPortrait => '1 kursi memenuhi layar, rasio potret 9:16',
            self::FullSquare => '1 kursi memenuhi layar, rasio kotak 1:1',
            self::FullLandscape => '1 kursi memenuhi layar, rasio lanskap 16:9',
            self::Grid2x2 => '4 kursi, grid 2 kolom x 2 baris',
            self::Grid3x3 => '9 kursi, grid 3 kolom x 3 baris',
            self::Grid4x4 => '16 kursi, grid 4 kolom x 4 baris',
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
            self::FullPortrait => 'images/layout-full-portrait.svg',
            self::FullSquare => 'images/layout-full-square.svg',
            self::FullLandscape => 'images/layout-full-landscape.svg',
            self::Grid2x2 => 'images/layout-grid-2x2.svg',
            self::Grid3x3 => 'images/layout-grid-3x3.svg',
            self::Grid4x4 => 'images/layout-grid-4x4.svg',
        };
    }

    /**
     * Angka murni (BUKAN class Tailwind!) buat susunan grid kursi — dipakai
     * blade lewat inline `style` (grid-template-columns/rows, aspect-ratio),
     * bukan class Tailwind dinamis. Tailwind cuma scan file .blade.php buat
     * tahu class mana yg perlu di-compile; class yg cuma ada di return string
     * PHP TIDAK PERNAH ke-generate ke CSS walau di-build berkali-kali (ini
     * penyebab bug sebelumnya) — makanya di sini SENGAJA cuma angka.
     */
    public function cols(): int
    {
        return match ($this) {
            self::Vertical => 2,
            self::Horizontal => 4,
            self::FullPortrait, self::FullSquare, self::FullLandscape => 1,
            self::Grid2x2 => 2,
            self::Grid3x3 => 3,
            self::Grid4x4 => 4,
        };
    }

    public function rows(): int
    {
        return match ($this) {
            self::Vertical => 4,
            self::Horizontal => 2,
            self::FullPortrait, self::FullSquare, self::FullLandscape => 1,
            self::Grid2x2 => 2,
            self::Grid3x3 => 3,
            self::Grid4x4 => 4,
        };
    }

    public function seatCount(): int
    {
        return $this->cols() * $this->rows();
    }

    /**
     * Rasio lebar:tinggi kontainer kursi — dipakai bareng ratioH() lewat
     * formula "contain" generik di blade (lihat live-show.blade.php),
     * otomatis pas di viewport apa pun tanpa perlu tahu ini potret/lanskap/
     * kotak. Grid2x2/3x3/4x4 sengaja 1:1 (kotak) — cols selalu = rows,
     * jadi tiap kursi otomatis persegi juga.
     */
    public function ratioW(): int
    {
        return match ($this) {
            self::Vertical => 1,
            self::Horizontal => 2,
            self::FullPortrait => 9,
            self::FullSquare => 1,
            self::FullLandscape => 16,
            self::Grid2x2, self::Grid3x3, self::Grid4x4 => 1,
        };
    }

    public function ratioH(): int
    {
        return match ($this) {
            self::Vertical => 2,
            self::Horizontal => 1,
            self::FullPortrait => 16,
            self::FullSquare => 1,
            self::FullLandscape => 9,
            self::Grid2x2, self::Grid3x3, self::Grid4x4 => 1,
        };
    }
}
