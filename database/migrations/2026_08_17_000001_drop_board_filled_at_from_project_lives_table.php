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
            // Auto-clear papan penuh dihapus — reset kursi sekarang murni manual
            // lewat tombol admin, kolom ini tidak dipakai lagi.
            $table->dropColumn('board_filled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_lives', function (Blueprint $table) {
            $table->timestamp('board_filled_at')->nullable()->after('webhook_secret');
        });
    }
};
