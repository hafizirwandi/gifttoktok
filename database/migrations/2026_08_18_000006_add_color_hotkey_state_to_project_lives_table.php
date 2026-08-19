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
        Schema::table('project_lives', function (Blueprint $table) {
            // Warna aktif SAAT INI hasil hotkey global (lihat project_live_color_hotkeys) —
            // begitu operator pencet hotkey warna di Live, semua kotak kursi yang masih
            // kosong ikut ganti warna+bayangan bareng-bareng. Null = tidak ada override
            // (kotak kosong balik pakai warna per-kursi masing-masing, ProjectLiveDetail::empty_bg_color).
            $table->string('active_hotkey_color', 7)->nullable()->after('frame_visible');

            // Hotkey khusus buat reset active_hotkey_color balik ke null (tombol "Default
            // Hotkey" di halaman Hotkey Warna, bisa juga ditekan langsung di Live).
            $table->char('default_color_hotkey', 1)->nullable()->after('active_hotkey_color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_lives', function (Blueprint $table) {
            $table->dropColumn(['active_hotkey_color', 'default_color_hotkey']);
        });
    }
};
