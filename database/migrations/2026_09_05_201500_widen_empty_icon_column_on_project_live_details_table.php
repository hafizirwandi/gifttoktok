<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * empty_icon dibuat sbg varchar(8) waktu masih menyimpan 1 karakter emoji (lihat
     * 2026_08_17_000002_add_empty_state_columns_to_project_live_details_table.php) -
     * sekarang menyimpan PATH GAMBAR hasil upload (jauh lebih panjang dari 8 karakter,
     * mis. "project-live-details/1/empty-icons/xxxx.png") jadi harus dilebarkan ke
     * default varchar(255) sama seperti kolom path lain (img, dst). Pakai raw SQL
     * (bukan Blueprint::change()) krn doctrine/dbal tidak terpasang di project ini.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE project_live_details MODIFY empty_icon VARCHAR(255) NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE project_live_details MODIFY empty_icon VARCHAR(8) NULL');
    }
};
