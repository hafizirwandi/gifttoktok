<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Warna area DI LUAR lingkaran (App\Enums\BackgroundFit::Circle) - sebelumnya
     * hardcode abu2 (bg-gray-700, sama spt avatar placeholder kosong), sekarang bisa
     * diatur admin per-BG lewat App\Livewire\ProjectLive\Background. Default
     * '#374151' = hex Tailwind gray-700, biar BG yang sudah ada (dibuat sebelum
     * kolom ini ada) tetap tampil PERSIS sama kayak sebelumnya, tidak berubah tiba2.
     */
    public function up(): void
    {
        Schema::table('project_live_backgrounds', function (Blueprint $table) {
            $table->string('circle_bg_color', 7)->default('#374151')->after('scale');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_live_backgrounds', function (Blueprint $table) {
            $table->dropColumn('circle_bg_color');
        });
    }
};
