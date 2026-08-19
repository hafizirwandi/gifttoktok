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
        Schema::table('project_live_details', function (Blueprint $table) {
            // Warna latar custom (hex) buat kotak yang MASIH KOSONG (fase "Request") —
            // null berarti pakai default hitam. Beda dari dominant_color yang cuma
            // dipakai kalau kursi sudah terisi.
            $table->string('empty_bg_color', 7)->nullable()->after('empty_icon');

            // Sistem follower manual sudah tidak dipakai lagi — semuanya sekarang
            // berjalan lewat coin (gift_total_value).
            $table->dropColumn('follower');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_live_details', function (Blueprint $table) {
            $table->string('follower')->nullable()->after('name');
            $table->dropColumn('empty_bg_color');
        });
    }
};
