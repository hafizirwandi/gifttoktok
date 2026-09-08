<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Override posisi icon mic PER KOTAK (beda dari mic_offset_x/y global di
     * project_lives yang berlaku ke semua kotak sekaligus) - null (default) = pakai
     * settingan global apa adanya, diisi angka kalau admin mau geser mic kotak ini
     * secara spesifik lewat modal edit di Preview Live. Lihat
     * partials/seat-box.blade.php & App\Livewire\ProjectLive\PreviewLive.
     */
    public function up(): void
    {
        Schema::table('project_live_details', function (Blueprint $table) {
            $table->integer('mic_offset_x')->nullable()->after('mic_visible');
            $table->integer('mic_offset_y')->nullable()->after('mic_offset_x');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_live_details', function (Blueprint $table) {
            $table->dropColumn(['mic_offset_x', 'mic_offset_y']);
        });
    }
};
