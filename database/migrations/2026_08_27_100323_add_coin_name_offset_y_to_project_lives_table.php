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
            $table->integer('coin_offset_y')->default(0)->after('mic_offset_y');
            $table->integer('name_offset_y')->default(0)->after('coin_offset_y');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_lives', function (Blueprint $table) {
            $table->dropColumn(['coin_offset_y', 'name_offset_y']);
        });
    }
};
