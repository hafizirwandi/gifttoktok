<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('project_live_color_hotkeys', function (Blueprint $table) {
            // Null = hotkey GLOBAL (semua kotak kosong ikut ganti warna). Diisi = hotkey
            // ini cuma ngefek ke SATU kursi ini. hotkey tetap unique per project_live_id
            // (constraint lama) apa pun targetnya, supaya tidak ambigu saat dipencet.
            $table->foreignId('project_live_detail_id')->nullable()->after('project_live_id')
                ->constrained('project_live_details')->cascadeOnDelete();
        });

        Schema::table('project_live_details', function (Blueprint $table) {
            // Warna aktif SAAT INI hasil hotkey PER-KURSI (beda dari
            // project_lives.active_hotkey_color yang global) — null berarti tidak ada
            // override per-kursi yang aktif.
            $table->string('active_hotkey_color', 7)->nullable()->after('empty_bg_color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_live_details', function (Blueprint $table) {
            $table->dropColumn('active_hotkey_color');
        });

        Schema::table('project_live_color_hotkeys', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_live_detail_id');
        });
    }
};
