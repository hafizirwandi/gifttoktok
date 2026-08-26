<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * mic_visible awalnya 1 saklar buat SEMUA kotak (kolom di project_lives) - user
     * minta bisa di-on/off PER KOTAK (diedit lewat modal Preview Live, sama spt
     * nama/foto/hotkey per kursi), jadi dipindah jadi kolom per-baris
     * project_live_details. Default true = sama spt sebelumnya (selalu tampil).
     */
    public function up(): void
    {
        Schema::table('project_live_details', function (Blueprint $table) {
            $table->boolean('mic_visible')->default(true)->after('empty_icon');
        });

        Schema::table('project_lives', function (Blueprint $table) {
            $table->dropColumn('mic_visible');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_lives', function (Blueprint $table) {
            $table->boolean('mic_visible')->default(true)->after('mic_size');
        });

        Schema::table('project_live_details', function (Blueprint $table) {
            $table->dropColumn('mic_visible');
        });
    }
};
