<?php

namespace App\Enums;

enum DisplayMode: string
{
    case Vertical = 'vertical';
    case Horizontal = 'horizontal';
    case FullPortrait = 'full_portrait';
    case FullSquare = 'full_square';
    case FullLandscape = 'full_landscape';

    public function label(): string
    {
        return match ($this) {
            self::Vertical => 'Vertical',
            self::Horizontal => 'Horizontal',
            self::FullPortrait => 'Layar Penuh (Potret)',
            self::FullSquare => 'Layar Penuh (Kotak)',
            self::FullLandscape => 'Layar Penuh (Lanskap)',
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
        };
    }

    public function rows(): int
    {
        return match ($this) {
            self::Vertical => 4,
            self::Horizontal => 2,
            self::FullPortrait, self::FullSquare, self::FullLandscape => 1,
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
     * kotak.
     */
    public function ratioW(): int
    {
        return match ($this) {
            self::Vertical => 1,
            self::Horizontal => 2,
            self::FullPortrait => 9,
            self::FullSquare => 1,
            self::FullLandscape => 16,
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
        };
    }
}
