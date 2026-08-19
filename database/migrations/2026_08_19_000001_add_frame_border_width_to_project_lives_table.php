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
            // Tebal border (px) frame di halaman Frame Host — dulu dipatok 8px, sekarang
            // custom.
            $table->unsignedSmallInteger('frame_border_width')->default(8)->after('frame_radius');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_lives', function (Blueprint $table) {
            $table->dropColumn('frame_border_width');
        });
    }
};
