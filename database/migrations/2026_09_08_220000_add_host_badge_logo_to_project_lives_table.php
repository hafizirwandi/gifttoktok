<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Logo/icon custom GLOBAL di samping tulisan badge "Host" (menggantikan icon
     * orang bawaan yang sudah dihapus) - fallback kalau kotak BG tidak punya logo
     * LOKAL sendiri (project_live_backgrounds.host_badge_logo). Null = tidak ada
     * logo sama sekali (badge cuma teks polos, perilaku default). Visible-nya
     * SENGAJA kolom terpisah (bukan disatuin ke host_badge_visible yang ngatur
     * SELURUH badge) - biar admin bisa matiin logo doang tanpa matiin tulisannya.
     */
    public function up(): void
    {
        Schema::table('project_lives', function (Blueprint $table) {
            $table->string('host_badge_logo')->nullable()->after('empty_icon');
            $table->boolean('host_badge_logo_visible')->default(true)->after('host_name_visible');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_lives', function (Blueprint $table) {
            $table->dropColumn(['host_badge_logo', 'host_badge_logo_visible']);
        });
    }
};
