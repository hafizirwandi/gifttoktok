<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Efek pulse kursi sekarang bisa dipilih LEBIH DARI 1 kotak sekaligus (checklist),
     * bukan cuma 1 nomor kursi - kolom lama (seat_pulse_position, 1 angka) diganti
     * jadi array JSON (seat_pulse_positions). Baris yang sebelumnya sudah punya
     * seat_pulse_position diisi dibungkus jadi array 1 elemen, biar setting-nya
     * tidak hilang.
     */
    public function up(): void
    {
        Schema::table('project_lives', function (Blueprint $table) {
            $table->json('seat_pulse_positions')->nullable()->after('seat_pulse_enabled');
        });

        DB::table('project_lives')->whereNotNull('seat_pulse_position')->get(['id', 'seat_pulse_position'])->each(function ($row) {
            DB::table('project_lives')->where('id', $row->id)->update([
                'seat_pulse_positions' => json_encode([(int) $row->seat_pulse_position]),
            ]);
        });

        Schema::table('project_lives', function (Blueprint $table) {
            $table->dropColumn('seat_pulse_position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_lives', function (Blueprint $table) {
            $table->unsignedTinyInteger('seat_pulse_position')->nullable()->after('seat_pulse_enabled');
        });

        DB::table('project_lives')->whereNotNull('seat_pulse_positions')->get(['id', 'seat_pulse_positions'])->each(function ($row) {
            $positions = json_decode($row->seat_pulse_positions, true) ?: [];

            DB::table('project_lives')->where('id', $row->id)->update([
                'seat_pulse_position' => $positions[0] ?? null,
            ]);
        });

        Schema::table('project_lives', function (Blueprint $table) {
            $table->dropColumn('seat_pulse_positions');
        });
    }
};
