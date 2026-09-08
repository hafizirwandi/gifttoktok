<?php

namespace Database\Seeders;

use App\Models\HostBadgePreset;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Preset warna/font tulisan badge "Host" - dibuat dari 4 contoh style di
 * assets/host/style1.png..style4.png (warna disamplr manual dari gambarnya,
 * urutan style1->style4 SENGAJA dipertahankan lewat sort_order). Upsert by
 * 'name' - aman dijalanin ulang (re-seed) tanpa bikin duplikat.
 */
class HostBadgePresetSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $presets = [
            // style1.png - badge peach/salmon lembut, teks/icon coklat tua.
            ['name' => 'Peach', 'bg_color' => '#FDBEA0', 'text_color' => '#5C3826', 'font' => null, 'sort_order' => 1],
            // style2.png - badge biru cerah, teks/icon putih.
            ['name' => 'Biru', 'bg_color' => '#29ABE2', 'text_color' => '#FFFFFF', 'font' => null, 'sort_order' => 2],
            // style3.png - badge kuning keemasan, teks/icon coklat tua (senada style1).
            ['name' => 'Emas', 'bg_color' => '#F6C863', 'text_color' => '#5C3826', 'font' => null, 'sort_order' => 3],
            // style4.png - badge gelap/hitam, teks putih.
            ['name' => 'Gelap', 'bg_color' => '#262626', 'text_color' => '#FFFFFF', 'font' => null, 'sort_order' => 4],
        ];

        $rows = array_map(fn (array $preset) => $preset + ['created_at' => $now, 'updated_at' => $now], $presets);

        HostBadgePreset::query()->upsert(
            $rows,
            ['name'],
            ['bg_color', 'text_color', 'font', 'sort_order', 'updated_at']
        );

        $this->command?->info(\count($presets).' host badge presets seeded.');
    }
}
