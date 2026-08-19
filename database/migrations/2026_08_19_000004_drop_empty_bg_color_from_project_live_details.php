<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('project_live_details', function (Blueprint $table) {
            // Warna statis per-kursi (diatur tanpa hotkey) dihapus — sudah ketutup total
            // sama hotkey warna per-kursi (project_live_color_hotkeys), jadi cukup satu
            // mekanisme CRUD hotkey saja, tidak dobel dengan panel warna statis terpisah.
            $table->dropColumn('empty_bg_color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_live_details', function (Blueprint $table) {
            $table->string('empty_bg_color', 7)->nullable()->after('empty_icon');
        });
    }
};
