<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Icon & font kotak kosong GLOBAL (default utk semua kotak) - fallback kalau
     * override LOKAL kotak (tombol "Custom" per kotak di Preview Live, project_live_
     * details.empty_icon/font) tidak diisi. Sama pola dgn mic_icon (icon) &
     * host_name_font (font) yang sudah ada - null = pakai perilaku default lama
     * ('+' hardcode, Figtree bawaan) - lihat App\Models\ProjectLive::emptyIconUrl()
     * & partials/seat-box.blade.php.
     */
    public function up(): void
    {
        Schema::table('project_lives', function (Blueprint $table) {
            $table->string('empty_icon')->nullable()->after('mic_icon');
            $table->string('empty_label_font')->nullable()->after('host_name_font');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_lives', function (Blueprint $table) {
            $table->dropColumn(['empty_icon', 'empty_label_font']);
        });
    }
};
