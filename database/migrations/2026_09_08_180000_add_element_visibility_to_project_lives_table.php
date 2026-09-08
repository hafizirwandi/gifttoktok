<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Show/hide GLOBAL per elemen (default utk semua kotak) - fallback kalau
     * override LOKAL kotak (tombol "Custom" di Preview Live, project_live_details.
     * style_overrides.{elemen}.visible) tidak diaktifkan. Default true = tampil
     * apa adanya spt sebelum fitur ini ada, TIDAK mengubah tampilan existing.
     *
     * mic_visible SEMPAT ada di tabel ini dulu (lihat migrasi
     * move_mic_visible_to_project_live_details_table) lalu dipindah total ke
     * project_live_details krn waktu itu HANYA butuh switch per-kotak - kolom ini
     * BEDA TUJUAN (default GLOBAL, bukan override per-kotak), aman dipakai lagi
     * krn migrasi lama sudah men-drop nama kolom yang sama dari tabel ini.
     */
    public function up(): void
    {
        Schema::table('project_lives', function (Blueprint $table) {
            $table->boolean('coin_visible')->default(true)->after('coin_size');
            $table->boolean('name_visible')->default(true)->after('name_size');
            $table->boolean('gift_badge_visible')->default(true)->after('gift_badge_size');
            $table->boolean('mic_visible')->default(true)->after('mic_size');
            $table->boolean('empty_icon_visible')->default(true)->after('empty_icon_size');
            $table->boolean('empty_label_visible')->default(true)->after('empty_label_size');
            $table->boolean('host_badge_visible')->default(true)->after('host_name_offset_y');
            $table->boolean('host_name_visible')->default(true)->after('host_badge_visible');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_lives', function (Blueprint $table) {
            $table->dropColumn([
                'coin_visible', 'name_visible', 'gift_badge_visible', 'mic_visible',
                'empty_icon_visible', 'empty_label_visible', 'host_badge_visible', 'host_name_visible',
            ]);
        });
    }
};
