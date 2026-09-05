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
        Schema::table('project_live_backgrounds', function (Blueprint $table) {
            // Cuma relevan kalau type=video - default false (senyap) sama spt perilaku
            // sebelumnya (semua video BG selalu di-mute hardcode). Kalau true, suaranya
            // CUMA nyala di halaman Live asli (browser source OBS) - Preview Live SELALU
            // dipaksa senyap apa pun nilai kolom ini, lihat partials/seat-box.blade.php
            // & live-show.blade.php/preview-live.blade.php ($allowAudio).
            $table->boolean('audio_enabled')->default(false)->after('host_badge_size');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_live_backgrounds', function (Blueprint $table) {
            $table->dropColumn('audio_enabled');
        });
    }
};
