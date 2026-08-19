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
            // Efek border Frame Host berkedip pelan (terang -> gelap -> terang) pakai
            // warna yang sama, bukan warna terpisah — lihat frame-host-live.blade.php.
            $table->boolean('frame_pulse')->default(false)->after('frame_visible');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_lives', function (Blueprint $table) {
            $table->dropColumn('frame_pulse');
        });
    }
};
