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
            // Efek pulse (border kotak berkedip gonta-ganti warna) versi buat 1 kotak
            // kursi tertentu di halaman Live - beda kolom dari frame_pulse_* (yg punya
            // FrameHost, overlay OBS terpisah) krn ini nempel di kotak KURSI itu
            // sendiri (lihat partials/seat-box.blade.php), pengaturan warna/kecepatan
            // sengaja dipisah (skenarionya beda, bisa aja mau warna pulse yg beda dari
            // border frame).
            $table->boolean('seat_pulse_enabled')->default(false)->after('seat_gap');
            // Posisi kursi (1-based, samain dgn project_live_details.position) yang
            // dapat efek pulse - null = belum dipilih/nonaktif.
            $table->unsignedTinyInteger('seat_pulse_position')->nullable()->after('seat_pulse_enabled');
            $table->unsignedInteger('seat_pulse_speed_ms')->default(1500)->after('seat_pulse_position');
            $table->string('seat_pulse_color_1', 7)->default('#1e3a8a')->after('seat_pulse_speed_ms');
            $table->string('seat_pulse_color_2', 7)->default('#38bdf8')->after('seat_pulse_color_1');
            $table->string('seat_pulse_color_3', 7)->nullable()->after('seat_pulse_color_2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_lives', function (Blueprint $table) {
            $table->dropColumn([
                'seat_pulse_enabled',
                'seat_pulse_position',
                'seat_pulse_speed_ms',
                'seat_pulse_color_1',
                'seat_pulse_color_2',
                'seat_pulse_color_3',
            ]);
        });
    }
};
