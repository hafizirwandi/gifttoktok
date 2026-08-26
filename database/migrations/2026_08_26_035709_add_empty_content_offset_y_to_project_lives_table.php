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
            // Geser posisi vertikal icon+teks kotak kosong (px, bisa negatif = naik,
            // positif = turun) - default 0 = posisi sekarang (center), tidak berubah
            // buat project lama. Signed (bukan unsignedTinyInteger spt BOX_STYLE_FIELDS
            // lain) krn nilainya dua arah.
            $table->integer('empty_content_offset_y')->default(0)->after('seat_gap');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_lives', function (Blueprint $table) {
            $table->dropColumn('empty_content_offset_y');
        });
    }
};
