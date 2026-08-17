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
            // Ikon gift TERAKHIR yang dikirim ke gifter kursi ini — tampil sebentar
            // (8 detik, lihat live-show) di pojok kanan atas lalu hilang. mapped_icon
            // (emoji) diutamakan; kalau gift-nya belum dipetakan, pakai icon_url gift itu.
            $table->string('last_gift_icon', 8)->nullable()->after('empty_icon');
            $table->string('last_gift_icon_url')->nullable()->after('last_gift_icon');
            $table->timestamp('last_gift_at')->nullable()->after('last_gift_icon_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_live_details', function (Blueprint $table) {
            $table->dropColumn(['last_gift_icon', 'last_gift_icon_url', 'last_gift_at']);
        });
    }
};
