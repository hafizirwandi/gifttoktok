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
            // Emoji custom yang tampil di kotak kursi kalau gift ini dikirim (mis. gift
            // "Donat" dipetakan ke 🦁) — null berarti pakai icon_url gift itu sendiri.
            // Unique: satu emoji cuma boleh dipakai untuk satu gift.
            $table->string('mapped_icon', 8)->nullable()->unique()->after('icon_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tiktok_gifts', function (Blueprint $table) {
            $table->dropColumn('mapped_icon');
        });
    }
};
