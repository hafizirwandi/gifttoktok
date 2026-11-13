<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Teks & font LOKAL (kotak BG ini saja) utk tulisan badge "Host" - null = ikut
     * GLOBAL (project_lives.host_badge_text/font). host_badge_offset_x/y yang SUDAH
     * ADA sejak awal (selalu NOT NULL, tanpa "ikut global") diubah jadi NULLABLE di
     * sini juga - null = ikut GLOBAL (project_lives.host_badge_offset_x/y) yang baru
     * ditambahkan, angka eksplisit (termasuk 0) = tetap posisi LOKAL kotak ini spt
     * sebelumnya. Row yang SUDAH ADA nilainya (biasanya 0 dari default lama) TIDAK
     * berubah jadi null otomatis - tetap perilaku LOKAL persis spt sebelum migrasi
     * ini, cuma sekarang BISA di-reset ke null (ikut global) lewat UI. Pakai
     * ->nullable()->change() (native sejak Laravel 11) supaya jalan di MySQL & SQLite.
     */
    public function up(): void
    {
        Schema::table('project_live_backgrounds', function (Blueprint $table) {
            $table->integer('host_badge_offset_x')->nullable()->default(null)->change();
            $table->integer('host_badge_offset_y')->nullable()->default(null)->change();
        });

        Schema::table('project_live_backgrounds', function (Blueprint $table) {
            $table->string('host_badge_text')->nullable()->after('host_name_visible');
            $table->string('host_badge_font')->nullable()->after('host_badge_text');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_live_backgrounds', function (Blueprint $table) {
            $table->dropColumn(['host_badge_text', 'host_badge_font']);
        });

        DB::statement('UPDATE project_live_backgrounds SET host_badge_offset_x = 0 WHERE host_badge_offset_x IS NULL');
        DB::statement('UPDATE project_live_backgrounds SET host_badge_offset_y = 0 WHERE host_badge_offset_y IS NULL');
        Schema::table('project_live_backgrounds', function (Blueprint $table) {
            $table->integer('host_badge_offset_x')->default(0)->change();
            $table->integer('host_badge_offset_y')->default(0)->change();
        });
    }
};
