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
            // 2 atau 3 warna yang di-cycle bergantian buat efek kedap-kedip border Frame
            // Host (lihat frame-host-live.blade.php) — warna_3 nullable, kalau kosong
            // cuma warna_1<->warna_2 yang dipakai (2 warna).
            $table->string('frame_pulse_color_1', 7)->default('#1e3a8a')->after('frame_pulse_speed_ms');
            $table->string('frame_pulse_color_2', 7)->default('#38bdf8')->after('frame_pulse_color_1');
            $table->string('frame_pulse_color_3', 7)->nullable()->after('frame_pulse_color_2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_lives', function (Blueprint $table) {
            $table->dropColumn(['frame_pulse_color_1', 'frame_pulse_color_2', 'frame_pulse_color_3']);
        });
    }
};
