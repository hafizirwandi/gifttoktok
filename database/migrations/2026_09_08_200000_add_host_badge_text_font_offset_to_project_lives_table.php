<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Isi teks, font, & posisi GLOBAL utk tulisan badge "Host" (App\Enums\SeatRole::
     * Host) - fallback kalau override LOKAL kotak BG (project_live_backgrounds.
     * host_badge_text/font/offset_x/offset_y) tidak diisi. Sama pola dgn
     * host_name_font/size/offset_x/offset_y yang sudah ada - null/0 = perilaku
     * default lama (teks literal "Host", font bawaan, posisi diam di top-2 left-2).
     */
    public function up(): void
    {
        Schema::table('project_lives', function (Blueprint $table) {
            $table->string('host_badge_text')->nullable()->after('host_name_visible');
            $table->string('host_badge_font')->nullable()->after('host_badge_text');
            $table->integer('host_badge_offset_x')->default(0)->after('host_badge_font');
            $table->integer('host_badge_offset_y')->default(0)->after('host_badge_offset_x');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_lives', function (Blueprint $table) {
            $table->dropColumn(['host_badge_text', 'host_badge_font', 'host_badge_offset_x', 'host_badge_offset_y']);
        });
    }
};
