<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * fit_mode adalah kolom ENUM asli (bukan string biasa, lihat migrasi
     * create_project_live_backgrounds_table) - nambah pilihan baru "circle" (media
     * ditampilkan sbg lingkaran di tengah kotak, bukan penuh edge-to-edge) HARUS lewat
     * MODIFY COLUMN mentah, Schema::table()->enum() tidak bisa mengubah definisi ENUM
     * yang sudah ada. Lihat App\Enums\BackgroundFit & partials/seat-box.blade.php.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE project_live_backgrounds MODIFY fit_mode ENUM('cover', 'contain', 'stretch', 'circle') NOT NULL DEFAULT 'cover'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("UPDATE project_live_backgrounds SET fit_mode = 'cover' WHERE fit_mode = 'circle'");
        DB::statement("ALTER TABLE project_live_backgrounds MODIFY fit_mode ENUM('cover', 'contain', 'stretch') NOT NULL DEFAULT 'cover'");
    }
};
