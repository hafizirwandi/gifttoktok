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
            // Setting halaman "Frame Host" — border dekoratif berbentuk portrait/landscape
            // yang dipasang sebagai OBS Browser Source terpisah (lihat frame-host-live).
            $table->string('frame_orientation')->default('portrait')->after('gift_badge_size');
            $table->string('frame_color', 7)->default('#38bdf8')->after('frame_orientation');
            $table->unsignedSmallInteger('frame_radius')->default(24)->after('frame_color');
            $table->boolean('frame_visible')->default(true)->after('frame_radius');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_lives', function (Blueprint $table) {
            $table->dropColumn(['frame_orientation', 'frame_color', 'frame_radius', 'frame_visible']);
        });
    }
};
