<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Dijalankan launcher versi desktop SETIAP kali versi aplikasi berubah (setelah
 * update), sesudah migrate. HANYA seeder yang aman diulang di atas data user:
 *
 * - TIDAK UserSeeder/ProjectLiveSeeder: akan memunculkan lagi akun/project contoh yang
 *   sudah dihapus user & syncRoles() mereset role-nya.
 * - TIDAK TikTokGiftSeeder: diawali TikTokGift::query()->delete(), jadi semua gift
 *   dibuat ulang dgn ID baru -> pemetaan & aturan gift milik user ikut hilang.
 *
 * Instalasi pertama tetap pakai DatabaseSeeder lengkap.
 */
class DesktopUpgradeSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            HostBadgePresetSeeder::class,
        ]);
    }
}
