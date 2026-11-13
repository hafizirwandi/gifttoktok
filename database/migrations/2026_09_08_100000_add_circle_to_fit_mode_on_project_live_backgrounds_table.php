<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * fit_mode adalah kolom ENUM asli (bukan string biasa, lihat migrasi
     * create_project_live_backgrounds_table) - nambah pilihan baru "circle" (media
     * ditampilkan sbg lingkaran di tengah kotak, bukan penuh edge-to-edge) lewat
     * ->enum()->change() (native sejak Laravel 11) - jalan di MySQL (MODIFY ENUM)
     * maupun SQLite (tabel dibangun ulang dgn CHECK constraint baru). Lihat App\Enums\BackgroundFit & partials/seat-box.blade.php.
     */
    public function up(): void
    {
        Schema::table('project_live_backgrounds', function (Blueprint $table) {
            $table->enum('fit_mode', ['cover', 'contain', 'stretch', 'circle'])->default('cover')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("UPDATE project_live_backgrounds SET fit_mode = 'cover' WHERE fit_mode = 'circle'");
        Schema::table('project_live_backgrounds', function (Blueprint $table) {
            $table->enum('fit_mode', ['cover', 'contain', 'stretch'])->default('cover')->change();
        });
    }
};
