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
        Schema::table('tiktok_gifts', function (Blueprint $table) {
            $table->dropColumn('mapped_icon');
        });

        Schema::table('tiktok_gifts', function (Blueprint $table) {
            // Pemetaan gift -> gift lain: ikon yang tampil di Live diambil dari icon_url
            // gift TUJUAN ini (bukan emoji lagi) — mis. gift "Donat" dipetakan ke gift
            // "Lion" di katalog, jadi begitu Donat dikirim, ikon Lion yang muncul.
            // Unique: satu gift tujuan cuma boleh jadi "wajah" untuk satu gift sumber.
            $table->foreignId('mapped_to_gift_id')->nullable()->unique()
                ->after('icon_url')
                ->constrained('tiktok_gifts')
                ->nullOnDelete();
        });

        Schema::table('project_live_details', function (Blueprint $table) {
            // Emoji custom sudah tidak dipakai lagi — ikon gift transient sekarang
            // selalu berupa gambar (last_gift_icon_url), diambil dari icon_url gift
            // tujuan pemetaan (atau icon_url gift itu sendiri kalau belum dipetakan).
            $table->dropColumn('last_gift_icon');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_live_details', function (Blueprint $table) {
            $table->string('last_gift_icon', 8)->nullable()->after('empty_icon');
        });

        Schema::table('tiktok_gifts', function (Blueprint $table) {
            $table->dropForeign(['mapped_to_gift_id']);
            $table->dropColumn('mapped_to_gift_id');
        });

        Schema::table('tiktok_gifts', function (Blueprint $table) {
            $table->string('mapped_icon', 8)->nullable()->unique()->after('icon_url');
        });
    }
};
