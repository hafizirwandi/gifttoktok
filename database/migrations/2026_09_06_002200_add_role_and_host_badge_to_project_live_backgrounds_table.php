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
            // Cuma relevan utk placement=seat - 'none' (default, perilaku BG biasa spt
            // sekarang), 'host' (tambah badge Host di pojok kiri atas, isi kotak tetap
            // cuma media BG), 'co_host' (kotak jadi spt kursi normal - nama/coin/mic,
            // tetap DIKECUALIKAN dari auto-gift selama background_id terisi, lihat
            // App\Services\GiftLeaderboardService::recalculate()).
            $table->string('role')->default('none')->after('scale');
            $table->string('host_badge_bg_color', 7)->default('#f59e0b')->after('role');
            $table->string('host_badge_text_color', 7)->default('#000000')->after('host_badge_bg_color');
            $table->unsignedSmallInteger('host_badge_size')->default(100)->after('host_badge_text_color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_live_backgrounds', function (Blueprint $table) {
            $table->dropColumn(['role', 'host_badge_bg_color', 'host_badge_text_color', 'host_badge_size']);
        });
    }
};
