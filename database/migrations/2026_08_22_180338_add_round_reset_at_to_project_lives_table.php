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
            // Kapan admin terakhir klik "Reset Leaderboard" — dipakai sbg batas "ronde
            // berjalan" (lihat GiftLeaderboardService::reset()/recalculate() dan
            // TikTokGiftEventProcessor::upsertGifter()) TANPA perlu menyentuh/menolkan
            // angka coin (round_value) siapa pun. Gifter yang last_gift_at-nya SEBELUM
            // timestamp ini dianggap "belum kontribusi di ronde baru" - tidak masuk
            // ranking kursi lagi sampai mereka benar2 kirim gift baru.
            $table->timestamp('round_reset_at')->nullable()->after('active_hotkey_color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_lives', function (Blueprint $table) {
            $table->dropColumn('round_reset_at');
        });
    }
};
