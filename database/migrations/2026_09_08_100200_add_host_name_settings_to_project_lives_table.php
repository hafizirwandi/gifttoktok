<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Settingan tampilan nama Host (App\Enums\SeatRole::Host) - teks BOLD tanpa badge
     * putih di pojok kiri bawah kotak, beda dari badge "Host" (warna/ukurannya per-BG,
     * lihat project_live_backgrounds.host_badge_*) dan beda dari badge Nama kursi
     * biasa (project_lives.name_size/name_offset_*, dipakai kursi normal & Co-Host).
     * Berlaku GLOBAL ke semua kotak yang jadi Host, diatur di halaman Admin (lihat
     * App\Livewire\ProjectLive\DetailAdmin) - font null = pakai default (Figtree).
     */
    public function up(): void
    {
        Schema::table('project_lives', function (Blueprint $table) {
            $table->string('host_name_font')->nullable()->after('gift_badge_offset_x');
            $table->unsignedSmallInteger('host_name_size')->default(100)->after('host_name_font');
            $table->integer('host_name_offset_x')->default(0)->after('host_name_size');
            $table->integer('host_name_offset_y')->default(0)->after('host_name_offset_x');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_lives', function (Blueprint $table) {
            $table->dropColumn(['host_name_font', 'host_name_size', 'host_name_offset_x', 'host_name_offset_y']);
        });
    }
};
