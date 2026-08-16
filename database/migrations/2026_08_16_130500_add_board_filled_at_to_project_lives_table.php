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
            // Diisi saat 8 kursi baru saja penuh; dipakai untuk menunda pengosongan
            // kursi (bukan langsung kosong seketika penuh) — lihat
            // GiftLeaderboardService::maybeStartNewRoundIfExpired().
            $table->timestamp('board_filled_at')->nullable()->after('webhook_secret');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_lives', function (Blueprint $table) {
            $table->dropColumn('board_filled_at');
        });
    }
};
