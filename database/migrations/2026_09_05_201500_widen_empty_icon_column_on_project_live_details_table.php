<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * empty_icon dibuat sbg varchar(8) waktu masih menyimpan 1 karakter emoji (lihat
     * 2026_08_17_000002_add_empty_state_columns_to_project_live_details_table.php) -
     * sekarang menyimpan PATH GAMBAR hasil upload (jauh lebih panjang dari 8 karakter,
     * mis. "project-live-details/1/empty-icons/xxxx.png") jadi harus dilebarkan ke
     * default varchar(255) sama seperti kolom path lain (img, dst). Pakai
     * Blueprint::change() (native sejak Laravel 11, tanpa doctrine/dbal) supaya jalan
     * di MySQL maupun SQLite (versi installer desktop).
     */
    public function up(): void
    {
        Schema::table('project_live_details', function (Blueprint $table) {
            $table->string('empty_icon', 255)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_live_details', function (Blueprint $table) {
            $table->string('empty_icon', 8)->nullable()->change();
        });
    }
};
