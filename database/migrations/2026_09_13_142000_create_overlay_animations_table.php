<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Katalog MASTER animasi overlay (WebP animasi, ditampilkan via <img> - BUKAN
     * <video>, animated WebP tidak punya event "ended" yang bisa disadap JS) - GLOBAL,
     * dipakai bareng semua project sama seperti katalog tiktok_gifts. duration_ms
     * diisi manual admin (seberapa lama animasinya "selesai" secara visual) karena
     * <img> tidak punya cara resmi mendeteksi animasi WebP sudah tamat - dipakai
     * App\Livewire\ProjectLive\OverlayShow buat auto-lanjut ke antrian berikutnya.
     */
    public function up(): void
    {
        Schema::create('overlay_animations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('file');
            $table->unsignedInteger('duration_ms')->default(3000);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overlay_animations');
    }
};
