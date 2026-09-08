<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Warna GLOBAL (berlaku ke semua kotak) yang jadi fallback kalau override LOKAL
     * kotak (project_live_details.border_color/empty_bg_color, tombol "Custom" di
     * Preview Live) tidak diaktifkan - null (default) = perilaku LAMA apa adanya
     * (border-white/15 dari class Tailwind, #000000 hardcode). Lihat
     * App\Livewire\ProjectLive\DetailAdmin & partials/seat-box.blade.php.
     */
    public function up(): void
    {
        Schema::table('project_lives', function (Blueprint $table) {
            $table->string('seat_border_color', 7)->nullable()->after('seat_border_radius');
            $table->string('seat_empty_bg_color', 7)->nullable()->after('seat_border_color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_lives', function (Blueprint $table) {
            $table->dropColumn(['seat_border_color', 'seat_empty_bg_color']);
        });
    }
};
