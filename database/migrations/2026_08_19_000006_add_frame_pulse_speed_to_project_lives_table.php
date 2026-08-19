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
            // Lama 1 siklus kedap-kedip (ms) — makin kecil makin cepat. Dipakai sebagai
            // animation-duration di frame-host-live.blade.php.
            $table->unsignedSmallInteger('frame_pulse_speed_ms')->default(1500)->after('frame_pulse');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_lives', function (Blueprint $table) {
            $table->dropColumn('frame_pulse_speed_ms');
        });
    }
};
