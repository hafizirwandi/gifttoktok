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
            // 'asc' = kotak index #1 diisi duluan lanjut ke bawah (perilaku lama,
            // default) - 'desc' = kebalikannya, kotak paling akhir diisi duluan
            // lanjut ke atas. Lihat App\Services\GiftLeaderboardService::recalculate().
            $table->string('seat_fill_direction', 4)->default('asc')->after('empty_content_offset_y');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_lives', function (Blueprint $table) {
            $table->dropColumn('seat_fill_direction');
        });
    }
};
