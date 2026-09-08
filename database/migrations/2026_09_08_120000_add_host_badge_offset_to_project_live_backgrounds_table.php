<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Naik/turun & geser kiri/kanan buat badge "Host" (App\Enums\SeatRole::Host) -
     * sebelumnya cuma bisa diubah warna/ukurannya (host_badge_size), posisinya kaku
     * di top-2 left-2. Per-BG (bukan global) sama kayak host_badge_bg_color/size,
     * diatur di modal "Style Badge Host" (App\Livewire\ProjectLive\PreviewLive::
     * saveBgEdit()).
     */
    public function up(): void
    {
        Schema::table('project_live_backgrounds', function (Blueprint $table) {
            $table->integer('host_badge_offset_x')->default(0)->after('host_badge_size');
            $table->integer('host_badge_offset_y')->default(0)->after('host_badge_offset_x');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_live_backgrounds', function (Blueprint $table) {
            $table->dropColumn(['host_badge_offset_x', 'host_badge_offset_y']);
        });
    }
};
