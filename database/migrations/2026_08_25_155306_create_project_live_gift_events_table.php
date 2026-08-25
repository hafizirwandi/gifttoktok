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
        Schema::create('project_live_gift_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_live_id')->constrained()->cascadeOnDelete();
            // Tidak menyimpan snapshot nickname/avatar di sini SENGAJA - selalu di-JOIN
            // ke project_live_gifters (via project_live_id+tiktok_user_id) utk tampilan,
            // biar selalu ikut nama/foto TERBARU yang sudah diketahui (bukan snapshot
            // beku saat itu), konsisten dgn cara ProjectLiveGifter sendiri dipakai di
            // tempat lain (leaderboard, seat kursi, dst).
            $table->string('tiktok_user_id');
            // nullOnDelete (bukan cascade) - kalau gift dihapus dari katalog, histori
            // pengiriman TETAP ada (poin sudah dibekukan di diamond_value), cuma link ke
            // detail gift-nya jadi null.
            $table->foreignId('tiktok_gift_id')->nullable()->constrained('tiktok_gifts')->nullOnDelete();
            $table->unsignedInteger('repeat_count')->default(1);
            // Poin dibekukan SAAT kejadian (repeat_count * tiktok_gifts.diamond_count
            // waktu itu) - sama alasannya dgn project_live_gifters.total_value/round_value,
            // krn diamond_count bisa diedit admin belakangan (lihat TikTokGiftEventProcessor).
            $table->unsignedBigInteger('diamond_value')->default(0);
            $table->timestamps();

            $table->index(['project_live_id', 'created_at']);
            $table->index(['project_live_id', 'tiktok_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_live_gift_events');
    }
};
