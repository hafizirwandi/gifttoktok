<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Logo/icon custom LOKAL (kotak BG ini saja) di samping tulisan badge "Host" -
     * null = ikut GLOBAL (project_lives.host_badge_logo). host_badge_logo_visible
     * null = ikut GLOBAL (project_lives.host_badge_logo_visible), true/false =
     * override LOKAL eksplisit - sama pola dgn host_badge_visible/host_name_visible
     * yang sudah ada.
     */
    public function up(): void
    {
        Schema::table('project_live_backgrounds', function (Blueprint $table) {
            $table->string('host_badge_logo')->nullable()->after('host_badge_font');
            $table->boolean('host_badge_logo_visible')->nullable()->after('host_badge_logo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_live_backgrounds', function (Blueprint $table) {
            $table->dropColumn(['host_badge_logo', 'host_badge_logo_visible']);
        });
    }
};
