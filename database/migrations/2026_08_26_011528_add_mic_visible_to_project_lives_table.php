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
            // Default true = ikon mic tetap tampil spt sebelumnya (tidak ada toggle
            // ini dulu, jadi selalu tampil) - project lama tidak berubah tampilan.
            $table->boolean('mic_visible')->default(true)->after('mic_size');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_lives', function (Blueprint $table) {
            $table->dropColumn('mic_visible');
        });
    }
};
