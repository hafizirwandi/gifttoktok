<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mode durasi tayang - 'manual' (admin isi sendiri, perilaku lama) atau 'auto'
     * (dihitung otomatis dari total durasi 1 loop animasi WebP-nya sendiri lewat
     * App\Support\WebpAnimation, dihitung ulang tiap kali file diganti). duration_ms
     * TETAP diisi di kedua mode (auto cuma nentuin ANGKANYA datang dari mana) supaya
     * App\Livewire\ProjectLive\OverlayShow tidak perlu tahu bedanya sama sekali.
     */
    public function up(): void
    {
        Schema::table('overlay_animations', function (Blueprint $table) {
            $table->string('duration_mode')->default('manual')->after('duration_ms');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('overlay_animations', function (Blueprint $table) {
            $table->dropColumn('duration_mode');
        });
    }
};
