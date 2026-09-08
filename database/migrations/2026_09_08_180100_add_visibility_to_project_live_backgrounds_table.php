<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Show/hide LOKAL (kotak BG ini saja) utk badge "Host" & Nama Host - nullable,
     * null = ikut default GLOBAL (project_lives.host_badge_visible/host_name_visible),
     * true/false = override eksplisit kotak ini. Diatur lewat modal "Edit Kotak BG"
     * (App\Livewire\ProjectLive\PreviewLive::saveBgEdit()), sama pola dgn
     * host_badge_bg_color dkk yang sudah ada (per-BG).
     */
    public function up(): void
    {
        Schema::table('project_live_backgrounds', function (Blueprint $table) {
            $table->boolean('host_badge_visible')->nullable()->after('host_badge_offset_y');
            $table->boolean('host_name_visible')->nullable()->after('host_badge_visible');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_live_backgrounds', function (Blueprint $table) {
            $table->dropColumn(['host_badge_visible', 'host_name_visible']);
        });
    }
};
