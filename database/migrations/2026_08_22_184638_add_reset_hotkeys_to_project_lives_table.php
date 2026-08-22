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
            // Hotkey yang dipencet di halaman Live (bukan Admin) buat langsung memicu
            // Reset Leaderboard / Reset Coin tanpa perlu pindah tab — lihat
            // LiveShow::triggerResetLeaderboard()/triggerResetCoins().
            $table->string('reset_leaderboard_hotkey', 1)->nullable()->after('default_color_hotkey');
            $table->string('reset_coin_hotkey', 1)->nullable()->after('reset_leaderboard_hotkey');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_lives', function (Blueprint $table) {
            $table->dropColumn(['reset_leaderboard_hotkey', 'reset_coin_hotkey']);
        });
    }
};
