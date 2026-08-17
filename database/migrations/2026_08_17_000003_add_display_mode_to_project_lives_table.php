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
        Schema::table('project_lives', function (Blueprint $table) {
            // Tata letak halaman Live: portrait (default, kursi di kanan), landscape
            // (kursi di bawah, 1 area konten), atau landscape_2host (2 area konten
            // berdampingan + kursi di bawah). Diatur admin, lihat App\Enums\DisplayMode.
            $table->string('display_mode')->default('portrait')->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_lives', function (Blueprint $table) {
            $table->dropColumn('display_mode');
        });
    }
};
