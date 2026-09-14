<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ganti tiktok_gifts.mapped_to_gift_id (satu gift tujuan doang) jadi pivot -
     * satu gift SUMBER sekarang boleh dipetakan ke LEBIH DARI SATU gift tujuan,
     * salah satu ikonnya dipilih ACAK tiap kali gift sumber itu diterima (lihat
     * App\Services\TikTokGiftEventProcessor::stampGiftIcon()). Data lama di-backfill
     * dulu ke pivot sebelum kolomnya dihapus, supaya pemetaan yang sudah ada admin
     * tidak hilang - sama pola dgn migration ganti project_live_event_triggers.
     * mapped_gift_id jadi pivot (2026_09_13_142002_...).
     */
    public function up(): void
    {
        Schema::create('tiktok_gift_mapped_targets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('source_gift_id');
            $table->unsignedBigInteger('target_gift_id');
            $table->timestamps();

            // Nama constraint di-persingkat manual (bukan default auto-generate
            // Laravel) - dua kolom di tabel yang sama-sama menunjuk ke tiktok_gifts
            // (self-referencing) gampang kepanjangan dari batas 64 karakter MySQL
            // kalau dibiarkan auto (lihat komentar migration pivot Event Trigger).
            $table->foreign('source_gift_id', 'gift_mapped_source_fk')
                ->references('id')->on('tiktok_gifts')->cascadeOnDelete();

            $table->foreign('target_gift_id', 'gift_mapped_target_fk')
                ->references('id')->on('tiktok_gifts')->cascadeOnDelete();

            $table->unique(['source_gift_id', 'target_gift_id'], 'gift_mapped_pair_unique');
        });

        $now = now();

        DB::table('tiktok_gifts')
            ->whereNotNull('mapped_to_gift_id')
            ->get(['id', 'mapped_to_gift_id'])
            ->each(function ($row) use ($now) {
                DB::table('tiktok_gift_mapped_targets')->insert([
                    'source_gift_id' => $row->id,
                    'target_gift_id' => $row->mapped_to_gift_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });

        Schema::table('tiktok_gifts', function (Blueprint $table) {
            $table->dropForeign(['mapped_to_gift_id']);
            $table->dropColumn('mapped_to_gift_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tiktok_gifts', function (Blueprint $table) {
            $table->foreignId('mapped_to_gift_id')->nullable()->after('icon_url')->constrained('tiktok_gifts')->nullOnDelete();
        });

        DB::table('tiktok_gift_mapped_targets')
            ->orderBy('id')
            ->get()
            ->groupBy('source_gift_id')
            ->each(function ($rows, $sourceId) {
                DB::table('tiktok_gifts')
                    ->whereKey($sourceId)
                    ->update(['mapped_to_gift_id' => $rows->first()->target_gift_id]);
            });

        Schema::dropIfExists('tiktok_gift_mapped_targets');
    }
};
