<?php

use App\Models\ProjectLive;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Preset "Panel Tetap" dihapus dari App\Enums\DisplayMode - project yang masih
     * pakai nilai lama 'panel_fixed' dipindah ke 'vertical' (default project baru),
     * biar DisplayMode::from() tidak ValueError pas project itu di-load. Kursi
     * disamakan lewat syncDetailsToDisplayMode() sesudah raw update, sama polanya
     * dgn migrasi grid_4x4 -> grid_3x3 sebelumnya.
     */
    public function up(): void
    {
        $ids = DB::table('project_lives')->where('display_mode', 'panel_fixed')->pluck('id');

        DB::table('project_lives')->where('display_mode', 'panel_fixed')->update(['display_mode' => 'vertical']);

        ProjectLive::whereIn('id', $ids)->get()->each(fn (ProjectLive $projectLive) => $projectLive->syncDetailsToDisplayMode());
    }

    /**
     * Tidak bisa dibalik ke 'panel_fixed' (case enum-nya sudah tidak ada lagi di
     * kode), jadi down() sengaja dikosongkan.
     */
    public function down(): void
    {
        //
    }
};
