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
            // Efek pulse kursi sekarang SELALU ikut warna/kecepatan Frame Host
            // (frame_pulse_color_1/2/3, frame_pulse_speed_ms) - admin cukup pilih
            // KOTAK mana yang berkedip, tidak perlu atur ulang warna/kecepatan
            // sendiri. Kolom warna/kecepatan terpisah punya kursi jadi tidak
            // kepakai lagi (seat_pulse_enabled & seat_pulse_position tetap ada).
            $table->dropColumn(['seat_pulse_speed_ms', 'seat_pulse_color_1', 'seat_pulse_color_2', 'seat_pulse_color_3']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_lives', function (Blueprint $table) {
            $table->unsignedInteger('seat_pulse_speed_ms')->default(1500)->after('seat_pulse_position');
            $table->string('seat_pulse_color_1', 7)->default('#1e3a8a')->after('seat_pulse_speed_ms');
            $table->string('seat_pulse_color_2', 7)->default('#38bdf8')->after('seat_pulse_color_1');
            $table->string('seat_pulse_color_3', 7)->nullable()->after('seat_pulse_color_2');
        });
    }
};
