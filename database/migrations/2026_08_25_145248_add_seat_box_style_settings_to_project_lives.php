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
            // Default 0 = tanpa padding (sama spt tampilan sebelum setting ini ada).
            $table->unsignedTinyInteger('seat_padding')->default(0)->after('mic_size');
            // Default 4 = sama persis dgn border-4 yg sebelumnya hardcode di
            // partials/seat-box.blade.php, supaya project lama tidak berubah tampilan.
            $table->unsignedTinyInteger('seat_border_width')->default(4)->after('seat_padding');
            // Default 12 = sama persis dgn rounded-xl (0.75rem) yg sebelumnya hardcode.
            $table->unsignedTinyInteger('seat_border_radius')->default(12)->after('seat_border_width');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_lives', function (Blueprint $table) {
            $table->dropColumn(['seat_padding', 'seat_border_width', 'seat_border_radius']);
        });
    }
};
