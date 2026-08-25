<?php

use App\Models\ProjectLive;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Preset "4x4" (16 kursi) dihapus dari App\Enums\DisplayMode - project yang masih
     * pakai nilai lama 'grid_4x4' dipindah ke '3x3' (9 kursi) yg paling dekat, biar
     * DisplayMode::from() tidak ValueError pas project itu di-load. Kursi posisi 10-16
     * disamakan lewat syncDetailsToDisplayMode() sesudah raw update (bukan dihapus
     * manual) - gifter/ledger tetap aman, ikut prinsip yg sama spt ganti tata letak
     * biasa lewat DetailAdmin::updateDisplayMode().
     */
    public function up(): void
    {
        $ids = DB::table('project_lives')->where('display_mode', 'grid_4x4')->pluck('id');

        DB::table('project_lives')->where('display_mode', 'grid_4x4')->update(['display_mode' => 'grid_3x3']);

        ProjectLive::whereIn('id', $ids)->get()->each(fn (ProjectLive $projectLive) => $projectLive->syncDetailsToDisplayMode());
    }

    /**
     * Tidak bisa dibalik ke 'grid_4x4' (case enum-nya sudah tidak ada lagi di kode),
     * jadi down() sengaja dikosongkan.
     */
    public function down(): void
    {
        //
    }
};
