<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Katalog MASTER (bukan per-project, dipakai bareng spt tiktok_gifts) preset
     * warna/font tulisan badge "Host" - admin bisa klik salah satu preset buat
     * ngisi hostBadgeBgColor/hostBadgeTextColor/hostBadgeFont langsung (App\
     * Livewire\ProjectLive\PreviewLive::applyHostBadgePreset()), atau tetap custom
     * manual lewat color picker/font picker yang sudah ada - preset cuma shortcut
     * ngisi awal, BUKAN kunci/lock ke preset itu.
     */
    public function up(): void
    {
        Schema::create('host_badge_presets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('bg_color', 7);
            $table->string('text_color', 7);
            $table->string('font')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('host_badge_presets');
    }
};
