<?php

namespace App\Enums;

enum DisplayMode: string
{
    case FullSquare = 'full_square';
    case Grid2x2 = 'grid_2x2';
    case Grid2x1 = 'grid_2x1';
    case Grid2x1Rect = 'grid_2x1_rect';
    case Grid3x3 = 'grid_3x3';
    case Grid3x3Rect = 'grid_3x3_rect';
    case FullLandscape = 'full_landscape';
    case Grid3x2 = 'grid_3x2';
    case Grid3x2Rect = 'grid_3x2_rect';
    case Horizontal = 'horizontal';
    case HorizontalRect = 'horizontal_rect';
    case FullPortrait = 'full_portrait';
    case Grid2x3 = 'grid_2x3';
    case Vertical = 'vertical';
    case Bersebelahan = 'side_by_side';
    case BersebelahanDua = 'side_by_side_2';
    case Sorotan = 'spotlight';
    case SorotanDua = 'spotlight_2';
    case SorotanTiga = 'spotlight_3';
    case SorotanEmpat = 'spotlight_4';
    case KisiDinamis = 'grid_dynamic';
    case SorotanEnam = 'spotlight_6';
    case SorotanTujuh = 'spotlight_7';
    case SorotanTujuhRect = 'spotlight_7_rect';
    case SorotanDelapan = 'spotlight_8';
    case MejaBundarSatu = 'round_table_1';
    case MejaBundarDua = 'round_table_2';

    public function label(): string
    {
        return match ($this) {
            self::FullSquare => 'Layar Penuh (Kotak)',
            self::Grid2x2 => '2x2',
            self::Grid2x1 => '2x1',
            self::Grid2x1Rect => '2x1 (Persegi Panjang)',
            self::Grid3x3 => '3x3',
            self::Grid3x3Rect => '3x3 (Persegi Panjang)',
            self::FullLandscape => 'Layar Penuh (Lanskap)',
            self::Grid3x2 => '3x2',
            self::Grid3x2Rect => '3x2 (Persegi Panjang)',
            self::Horizontal => '4x2',
            self::HorizontalRect => '4x2 (Persegi Panjang)',
            self::FullPortrait => 'Layar Penuh (Potret)',
            self::Grid2x3 => '2x3',
            self::Vertical => '2x4',
            self::KisiDinamis => 'Sorotan V',
            self::Bersebelahan => 'Bersebelahan',
            self::BersebelahanDua => 'Bersebelahan II',
            self::Sorotan => 'Sorotan',
            self::SorotanDua => 'Sorotan II',
            self::SorotanTiga => 'Sorotan III',
            self::SorotanEmpat => 'Sorotan IV',
            self::SorotanEnam => 'Sorotan VI',
            self::SorotanTujuh => 'Sorotan VII',
            self::SorotanTujuhRect => 'Sorotan VII (Persegi Panjang)',
            self::SorotanDelapan => 'Sorotan VIII',
            self::MejaBundarSatu => 'Meja Bundar I',
            self::MejaBundarDua => 'Meja Bundar II',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::FullSquare => '1 kursi memenuhi layar, rasio kotak 1:1',
            self::Grid2x2 => '4 kursi, grid 2 kolom x 2 baris',
            self::Grid2x1 => '2 kursi persegi berdampingan (2 kolom x 1 baris)',
            self::Grid2x1Rect => '2 kursi persegi panjang berdampingan (spt split-screen duet), kontainer 9:16',
            self::Grid3x3 => '9 kursi, grid 3 kolom x 3 baris',
            self::Grid3x3Rect => '9 kursi, grid 3 kolom x 3 baris (kontainer persegi panjang, bukan kotak)',
            self::FullLandscape => '1 kursi memenuhi layar, rasio lanskap 16:9',
            self::Grid3x2 => '6 kursi, grid 3 kolom x 2 baris',
            self::Grid3x2Rect => '6 kursi, grid 3 kolom x 2 baris (kontainer persegi panjang, bukan kotak)',
            self::Horizontal => 'Kursi memenuhi lebar layar (4 kolom x 2 baris)',
            self::HorizontalRect => '8 kursi, grid 4 kolom x 2 baris (kontainer persegi panjang, bukan rasio kotak per-kursi)',
            self::FullPortrait => '1 kursi memenuhi layar, rasio potret 9:16',
            self::Grid2x3 => '6 kursi, grid 2 kolom x 3 baris',
            self::Vertical => 'Kursi memenuhi tinggi layar (2 kolom x 4 baris)',
            self::KisiDinamis => '3 kursi: 1 kursi besar (persegi panjang, menyesuaikan) di kiri + 2 kursi persegi bertumpuk di kanan',
            self::Bersebelahan => '10 kursi, semua persegi: 2 kursi besar (2x ukuran) berdampingan di atas + 8 kursi kecil (4x2) di bawah',
            self::BersebelahanDua => '6 kursi: 2 kursi besar (2 lebar x 3 tinggi kotak) berdampingan di atas + 4 kursi persegi (4x1) di bawah',
            self::Sorotan => '10 kursi, semua persegi: 1 kursi sorotan (lebar penuh, tinggi = 1 kotak) di atas + grid 3x3 di bawah',
            self::SorotanDua => '9 kursi, semua persegi: 1 kursi sorotan (lebar penuh, tinggi = 2x kotak) di atas + grid 4x2 di bawah',
            self::SorotanTiga => '5 kursi, semua persegi: 1 kursi sorotan (lebar penuh, tinggi = 3x kotak) di atas + grid 4x1 di bawah',
            self::SorotanEmpat => '9 kursi, mode vertikal: 1 kursi besar di kiri (4x lebar, 8x tinggi kotak) + 8 kursi persegi bertumpuk (1x8) di kanan',
            self::SorotanEnam => '4 kursi: 1 kursi besar di kiri (2x lebar, 3x tinggi kotak) + 3 kursi persegi bertumpuk (1x3) di kanan',
            self::SorotanTujuh => '5 kursi: 1 kursi besar di kiri (3x lebar, 4x tinggi kotak) + 4 kursi persegi bertumpuk (1x4) di kanan',
            self::SorotanTujuhRect => '5 kursi: sama spt Sorotan VII, tapi kursi kecilnya persegi panjang (kontainer lebih tinggi, bukan rasio kotak)',
            self::SorotanDelapan => '7 kursi: 1 kursi besar di kiri (4x lebar, 6x tinggi kotak) + 6 kursi persegi bertumpuk (1x6) di kanan',
            self::MejaBundarSatu => '8 kursi, grid unit 4x4: 4 kursi persegi kiri + 4 kursi persegi kanan, celah kosong 2x4 di tengah',
            self::MejaBundarDua => '9 kursi, grid unit 4x4: sama spt Meja Bundar I, tapi celah tengahnya diisi 1 kursi persegi panjang (2x lebar, 4x tinggi kotak)',
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
            self::FullSquare => 'images/layout-full-square.svg',
            self::Grid2x2 => 'images/layout-grid-2x2.svg',
            self::Grid2x1 => 'images/layout-grid-2x1.svg',
            self::Grid2x1Rect => 'images/layout-grid-2x1-rect.svg',
            self::Grid3x3 => 'images/layout-grid-3x3.svg',
            self::Grid3x3Rect => 'images/layout-grid-3x3-rect.svg',
            self::FullLandscape => 'images/layout-full-landscape.svg',
            self::Grid3x2 => 'images/layout-grid-3x2.svg',
            self::Grid3x2Rect => 'images/layout-grid-3x2-rect.svg',
            self::Horizontal => 'images/layout-horizontal.svg',
            self::HorizontalRect => 'images/layout-horizontal-rect.svg',
            self::FullPortrait => 'images/layout-full-portrait.svg',
            self::Grid2x3 => 'images/layout-grid-2x3.svg',
            self::Vertical => 'images/layout-vertical.svg',
            self::KisiDinamis => 'images/layout-spotlight-5.svg',
            self::Bersebelahan => 'images/layout-side-by-side.svg',
            self::BersebelahanDua => 'images/layout-side-by-side-2.svg',
            self::Sorotan => 'images/layout-spotlight.svg',
            self::SorotanDua => 'images/layout-spotlight-2.svg',
            self::SorotanTiga => 'images/layout-spotlight-3.svg',
            self::SorotanEmpat => 'images/layout-spotlight-4.svg',
            self::SorotanEnam => 'images/layout-spotlight-6.svg',
            self::SorotanTujuh => 'images/layout-spotlight-7.svg',
            self::SorotanTujuhRect => 'images/layout-spotlight-7-rect.svg',
            self::SorotanDelapan => 'images/layout-spotlight-8.svg',
            self::MejaBundarSatu => 'images/layout-round-table-1.svg',
            self::MejaBundarDua => 'images/layout-round-table-2.svg',
        };
    }

    /**
     * Angka murni (BUKAN class Tailwind!) buat susunan grid kursi — dipakai
     * blade lewat inline `style` (grid-template-columns/rows, aspect-ratio),
     * bukan class Tailwind dinamis. Tailwind cuma scan file .blade.php buat
     * tahu class mana yg perlu di-compile; class yg cuma ada di return string
     * PHP TIDAK PERNAH ke-generate ke CSS walau di-build berkali-kali (ini
     * penyebab bug sebelumnya) — makanya di sini SENGAJA cuma angka. Buat 6
     * mode "mosaik" (KisiDinamis dst) angka ini cuma jumlah TRACK grid CSS-nya
     * (bukan jumlah kursi per kolom/baris beneran, krn kursinya tidak seragam) -
     * dipakai sbg fallback default gridTemplateColumns()/gridTemplateRows(),
     * seatCount() mode2 itu di-override eksplisit (lihat method itu).
     */
    public function cols(): int
    {
        return match ($this) {
            self::Vertical => 2,
            self::Horizontal, self::HorizontalRect => 4,
            self::FullPortrait, self::FullSquare, self::FullLandscape => 1,
            self::Grid2x2, self::Grid2x1, self::Grid2x1Rect => 2,
            self::Grid3x3, self::Grid3x3Rect => 3,
            self::Grid2x3 => 2,
            self::Grid3x2, self::Grid3x2Rect => 3,
            self::KisiDinamis => 2,
            self::Bersebelahan, self::BersebelahanDua, self::SorotanDua, self::SorotanTiga, self::MejaBundarSatu, self::MejaBundarDua => 4,
            self::Sorotan => 3,
            self::SorotanEnam => 3,
            self::SorotanEmpat, self::SorotanDelapan => 5,
            self::SorotanTujuh, self::SorotanTujuhRect => 4,
        };
    }

    public function rows(): int
    {
        return match ($this) {
            self::Vertical => 4,
            self::Horizontal, self::HorizontalRect => 2,
            self::FullPortrait, self::FullSquare, self::FullLandscape, self::Grid2x1, self::Grid2x1Rect => 1,
            self::Grid2x2 => 2,
            self::Grid3x3, self::Grid3x3Rect => 3,
            self::Grid2x3 => 3,
            self::Grid3x2, self::Grid3x2Rect => 2,
            self::KisiDinamis => 2,
            self::SorotanEnam => 3,
            self::Bersebelahan, self::BersebelahanDua, self::Sorotan, self::SorotanDua, self::SorotanTiga, self::MejaBundarSatu, self::MejaBundarDua, self::SorotanTujuh, self::SorotanTujuhRect => 4,
            self::SorotanEmpat => 8,
            self::SorotanDelapan => 6,
        };
    }

    /**
     * Jumlah kursi beneran. Utk grid seragam (9 mode pertama) selalu cols()*rows().
     * Utk 6 mode "mosaik" (kotak ukuran beda-beda / ada celah kosong) TIDAK bisa
     * dihitung dari cols()*rows() lagi (itu cuma jumlah track CSS-nya) - jadi
     * di-override manual sesuai jumlah kursi asli di gridTemplateAreas().
     */
    public function seatCount(): int
    {
        return match ($this) {
            self::KisiDinamis => 3,
            self::Bersebelahan => 10,
            self::BersebelahanDua => 6,
            self::Sorotan => 10,
            self::SorotanDua => 9,
            self::SorotanTiga => 5,
            self::SorotanEmpat => 9,
            self::SorotanEnam => 4,
            self::SorotanTujuh, self::SorotanTujuhRect => 5,
            self::SorotanDelapan => 7,
            self::MejaBundarSatu => 8,
            self::MejaBundarDua => 9,
            default => $this->cols() * $this->rows(),
        };
    }

    /**
     * Rasio lebar:tinggi kontainer kursi — dipakai bareng ratioH() lewat
     * formula "contain" generik di blade (lihat live-show.blade.php),
     * otomatis pas di viewport apa pun tanpa perlu tahu ini potret/lanskap/
     * kotak. Grid2x2/3x3 sengaja 1:1 (kotak) — cols selalu = rows, jadi
     * tiap kursi otomatis persegi juga. Grid2x3/3x2 pakai rasio kontainer
     * sesuai jumlah kolom/baris-nya (bukan 1:1) supaya kotak per-kursinya
     * tetap persegi juga (2 kolom x 3 baris => rasio kontainer 2:3, dst).
     * 4 dari 6 mode "mosaik" pertama pakai 9:16 (rasio HP asli, spt Layar
     * Penuh). Bersebelahan, Sorotan, & Sorotan II JUSTRU rasionya ikut
     * PROPORSI UNIT GRID-nya masing2 (bukan dipilih bebas) - krn SEMUA
     * kursinya (besar maupun kecil) persegi asli lewat unit grid yang
     * seragam (lihat gridTemplateColumns/Rows/Areas), dan unit grid
     * seragam cuma jadi persegi kalau kontainernya sendiri juga punya
     * rasio (jumlah kolom unit) : (jumlah baris unit) - sama alasannya
     * dgn Grid2x2/3x3: Bersebelahan = 4 kolom:4 baris = 1:1, Sorotan =
     * 3 kolom:4 baris (1 baris sorotan + 3 baris grid 3x3) = 3:4, Sorotan
     * II = 4 kolom:4 baris (2 baris sorotan setinggi 2x kotak + 2 baris
     * grid 4x2) = 1:1, Sorotan III = 4 kolom:4 baris (3 baris sorotan
     * setinggi 3x kotak + 1 baris grid 4x1) = 1:1 juga. Sorotan IV BEDA
     * lagi - vertikal (5 kolom:8 baris), krn kursi besarnya di KIRI (bukan
     * di atas) selebar 4 unit x tinggi 8 unit, kursi kecilnya numpuk 1
     * kolom x 8 baris di kanan. Kisi Dinamis
     * PENGECUALIAN lagi - lihat intrinsicHeight(), rasio di sini cuma
     * dipakai buat batas LEBAR-nya, tinggi kontainernya menyesuaikan
     * konten (bukan dipaksa pas rasio 9:16).
     */
    public function ratioW(): int
    {
        return match ($this) {
            self::Vertical => 1,
            self::Horizontal, self::Grid2x1 => 2,
            self::FullPortrait, self::Grid2x1Rect => 9,
            self::FullSquare => 1,
            self::FullLandscape => 16,
            self::Grid2x2, self::Grid3x3, self::Bersebelahan, self::BersebelahanDua, self::SorotanDua, self::SorotanTiga, self::SorotanEnam, self::SorotanTujuh, self::MejaBundarSatu, self::MejaBundarDua => 1,
            self::Grid2x3 => 2,
            self::Grid3x2, self::Sorotan => 3,
            self::KisiDinamis => 9,
            self::SorotanEmpat => 5,
            self::Grid3x3Rect, self::Grid3x2Rect, self::SorotanTujuhRect => 4,
            self::HorizontalRect => 8,
            self::SorotanDelapan => 5,
        };
    }

    public function ratioH(): int
    {
        return match ($this) {
            self::Vertical => 2,
            self::Horizontal, self::Grid2x1 => 1,
            self::FullPortrait, self::Grid2x1Rect => 16,
            self::FullSquare => 1,
            self::FullLandscape => 9,
            self::Grid2x2, self::Grid3x3, self::Bersebelahan, self::BersebelahanDua, self::SorotanDua, self::SorotanTiga, self::SorotanEnam, self::SorotanTujuh, self::MejaBundarSatu, self::MejaBundarDua => 1,
            self::Grid2x3 => 3,
            self::Grid3x2 => 2,
            self::Sorotan => 4,
            self::KisiDinamis => 16,
            self::SorotanEmpat => 8,
            self::Grid3x3Rect, self::Grid3x2Rect, self::SorotanTujuhRect => 5,
            self::HorizontalRect => 5,
            self::SorotanDelapan => 6,
        };
    }

    /**
     * true kalau tinggi kontainer TIDAK boleh dipaksa pas rasio ratioW()/ratioH()
     * (dibiarkan `height: auto`, ikut tinggi konten aslinya) - dipakai Kisi Dinamis
     * krn 2 kursi kanannya HARUS persegi asli (lihat seatStyleOverrides()), jadi
     * tinggi totalnya ditentukan kursi itu (2x sisi persegi), BUKAN dipaksa
     * memenuhi rasio 9:16 - kalau dipaksa, kursi kanan jadi gepeng/lonjong,
     * bukan persegi asli lagi. Mode lain semua false (default, tinggi dipaksa
     * spt biasa lewat calc() di blade).
     */
    public function intrinsicHeight(): bool
    {
        return $this === self::KisiDinamis;
    }

    /**
     * CSS `grid-template-columns` — dipakai LANGSUNG di blade (bukan cuma cols()),
     * krn mode "mosaik" butuh track lebar TIDAK SERAGAM (mis. kolom kiri lebih
     * lebar dari kanan). Mode seragam (termasuk Meja Bundar I/II sekarang, unit
     * grid 4x4 seragam) tetap `repeat(cols,1fr)` spt biasa (fallback default
     * di bawah, tidak perlu arm eksplisit lagi di sini).
     */
    public function gridTemplateColumns(): string
    {
        return match ($this) {
            self::KisiDinamis => '3fr 2fr',
            self::Sorotan, self::SorotanEnam => 'repeat(3, 1fr)',
            self::SorotanEmpat, self::SorotanDelapan => 'repeat(5, 1fr)',
            default => 'repeat('.$this->cols().', 1fr)',
        };
    }

    /**
     * CSS `grid-template-rows` — sama alasannya dgn gridTemplateColumns(), baris
     * di mode "mosaik" ada yg sengaja lebih tinggi (mis. baris tengah "Panel
     * Tetap" atau baris sorotan di "Sorotan"). Kisi Dinamis pakai `auto` (bukan
     * `1fr`) krn tingginya HARUS ikut ukuran persegi kursi kanan (lihat
     * intrinsicHeight() & seatStyleOverrides()), bukan dipaksa 50%-50% dari
     * tinggi kontainer yang sudah dipatok duluan.
     */
    public function gridTemplateRows(): string
    {
        return match ($this) {
            self::KisiDinamis => 'repeat(2, auto)',
            self::Bersebelahan, self::BersebelahanDua, self::Sorotan, self::SorotanDua, self::SorotanTiga, self::MejaBundarSatu, self::MejaBundarDua => 'repeat(4, 1fr)',
            self::SorotanEmpat => 'repeat(8, 1fr)',
            self::SorotanEnam => 'repeat(3, 1fr)',
            default => 'repeat('.$this->rows().', 1fr)',
        };
    }

    /**
     * CSS `grid-template-areas` — null utk 9 mode grid seragam (kursi ditempatkan
     * otomatis mengikuti urutan DOM, spt sebelumnya, TIDAK perlu grid-area).
     * Utk 6 mode "mosaik", tiap baris di sini adalah SATU baris grid (dibungkus
     * tanda kutip krn syntax CSS-nya begitu) - token "s1".."sN" adalah nama area,
     * berurutan sesuai POSISI kursi (kursi posisi 1 = area "s1", dst - lihat
     * partials/seat-box.blade.php & preview-live.blade.php yg nulis
     * `grid-area: s{position}` per kotak). Titik "." = sel kosong (tanpa kursi).
     * Meja Bundar I = grid unit 4 kolom x 4 baris SERAGAM (kontainer persegi,
     * lihat ratioW/ratioH) - kursi kiri (s1-s4) di kolom 1, celah kosong 2x4 di
     * kolom 2-3, kursi kanan (s5-s8) di kolom 4. Meja Bundar II sama persis,
     * cuma celah tengahnya DIISI 1 kursi persegi panjang (s5, span kolom 2-3 x
     * semua baris = 2x lebar, 4x tinggi unit) bukan kosong. Bersebelahan
     * = grid unit 4 kolom x 4 baris SERAGAM (semua unit persegi krn kontainer
     * juga persegi, lihat ratioW/ratioH) - 2 kursi besar (s1,s2) masing2 span
     * 2 kolom x 2 baris (makanya otomatis 2x ukuran unit & tetap persegi), 8
     * kursi kecil (s3-s10) di 2 baris bawah masing2 1 unit. Sorotan (3 kolom
     * unit x 4 baris unit) mirip, kursi sorotan (s1) cuma 1 baris unit
     * (tinggi = 1 kotak). Sorotan II (4 kolom unit x 4 baris unit) kursi
     * sorotannya (s1) span 2 baris unit (tinggi = 2x kotak), grid kecilnya
     * 4x2 (bukan 3x3).
     */
    public function gridTemplateAreas(): ?string
    {
        return match ($this) {
            self::KisiDinamis => '"s1 s2" "s1 s3"',
            self::Bersebelahan => '"s1 s1 s2 s2" "s1 s1 s2 s2" "s3 s4 s5 s6" "s7 s8 s9 s10"',
            self::BersebelahanDua => '"s1 s1 s2 s2" "s1 s1 s2 s2" "s1 s1 s2 s2" "s3 s4 s5 s6"',
            self::Sorotan => '"s1 s1 s1" "s2 s3 s4" "s5 s6 s7" "s8 s9 s10"',
            self::SorotanDua => '"s1 s1 s1 s1" "s1 s1 s1 s1" "s2 s3 s4 s5" "s6 s7 s8 s9"',
            self::SorotanTiga => '"s1 s1 s1 s1" "s1 s1 s1 s1" "s1 s1 s1 s1" "s2 s3 s4 s5"',
            self::SorotanEmpat => '"s1 s1 s1 s1 s2" "s1 s1 s1 s1 s3" "s1 s1 s1 s1 s4" "s1 s1 s1 s1 s5" "s1 s1 s1 s1 s6" "s1 s1 s1 s1 s7" "s1 s1 s1 s1 s8" "s1 s1 s1 s1 s9"',
            self::SorotanEnam => '"s1 s1 s2" "s1 s1 s3" "s1 s1 s4"',
            self::SorotanTujuh, self::SorotanTujuhRect => '"s1 s1 s1 s2" "s1 s1 s1 s3" "s1 s1 s1 s4" "s1 s1 s1 s5"',
            self::SorotanDelapan => '"s1 s1 s1 s1 s2" "s1 s1 s1 s1 s3" "s1 s1 s1 s1 s4" "s1 s1 s1 s1 s5" "s1 s1 s1 s1 s6" "s1 s1 s1 s1 s7"',
            self::MejaBundarSatu => '"s1 . . s5" "s2 . . s6" "s3 . . s7" "s4 . . s8"',
            self::MejaBundarDua => '"s1 s5 s5 s6" "s2 s5 s5 s7" "s3 s5 s5 s8" "s4 s5 s5 s9"',
            default => null,
        };
    }

    /**
     * Style CSS TAMBAHAN per POSISI kursi (bukan per mode) - dipakai kalau ada
     * kursi tertentu di sebuah mode yang perlu bentuk KHUSUS beda dari kursi
     * lain di mode yang sama (jadi tidak bisa cukup lewat gridTemplateColumns/
     * Rows() yang berlaku utk SEMUA kursi). Satu-satunya kasus sekarang: Kisi
     * Dinamis kursi #2 #3 (kanan) - lebar sudah pasti (dari kolom fr), tinggi
     * di-derive dari CSS `aspect-ratio: 1/1` supaya jadi PERSEGI ASLI, makanya
     * override `height: auto` (nimpa class h-full) BUKAN tinggi tetap, biar
     * aspect-ratio yang nentuin. (Meja Bundar II TIDAK perlu trik ini lagi -
     * sejak jadi unit grid 4x4 seragam, kursi tengahnya otomatis persegi
     * panjang yg benar tanpa override apa pun, lihat gridTemplateAreas().)
     * Kunci array = posisi kursi (1-based), value = fragment CSS inline yang
     * ditambahkan ke style seat-box (lihat partials/seat-box.blade.php &
     * preview-live.blade.php).
     */
    public function seatStyleOverrides(): array
    {
        return match ($this) {
            self::KisiDinamis => [
                2 => 'aspect-ratio: 1 / 1; height: auto;',
                3 => 'aspect-ratio: 1 / 1; height: auto;',
            ],
            default => [],
        };
    }
}
