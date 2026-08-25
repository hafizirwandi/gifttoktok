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
            // Default 9:16 = sama persis dgn orientasi 'portrait' yg sebelumnya jadi
            // default project_lives.frame_orientation, biar project lama tidak berubah
            // tampilan. Tombol preset Portrait/Landscape/Persegi di FrameHost tinggal
            // MENGISI dua kolom ini ke rasio bawaannya (lihat FrameOrientation::ratioW()/
            // ratioH()) - tapi admin bebas ubah manual ke angka lain sesudahnya ("custom
            // width/height").
            $table->unsignedSmallInteger('frame_ratio_w')->default(9)->after('frame_orientation');
            $table->unsignedSmallInteger('frame_ratio_h')->default(16)->after('frame_ratio_w');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_lives', function (Blueprint $table) {
            $table->dropColumn(['frame_ratio_w', 'frame_ratio_h']);
        });
    }
};
