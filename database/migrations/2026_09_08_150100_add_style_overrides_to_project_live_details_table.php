<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Panel "Custom" per kotak (tombol baru di tiap kartu Preview Live) - satu kolom
     * JSON menyimpan override LOKAL utk beberapa elemen visual kotak sekaligus
     * (coin/nama/icon pemetaan gift/mic/icon & teks kotak kosong), MASING2 dgn flag
     * "enabled" sendiri - true = pakai size/offset_x/offset_y (dst) dari JSON ini,
     * false/tidak ada = pakai setting GLOBAL project_lives apa adanya (sama spt
     * sebelumnya). Lihat App\Livewire\ProjectLive\PreviewLive::STYLE_ELEMENTS utk
     * struktur lengkapnya & partials/seat-box.blade.php utk cara resolve-nya.
     *
     * mic_offset_x/y (kolom nullable simpel yang ditambah minggu lalu) DIHAPUS di
     * migrasi ini - fungsinya sekarang diambil alih 'mic' di dalam style_overrides
     * (yang juga menambah size & upload icon per-kotak, bukan cuma posisi), supaya
     * TIDAK ADA 2 mekanisme beda utk hal yang sama.
     *
     * empty_bg_color: warna latar kotak KOSONG (belum ada interaksi) KHUSUS kursi
     * ini - null (default) = pakai rantai prioritas lama apa adanya (hotkey per-
     * kursi > hotkey global > hitam #000000). Kalau diisi, jadi lapisan PALING
     * BAWAH di rantai itu (menggantikan hitam), hotkey (yang sifatnya live/dinamis
     * pas siaran) tetap menang di atasnya - lihat partials/seat-box.blade.php.
     */
    public function up(): void
    {
        Schema::table('project_live_details', function (Blueprint $table) {
            $table->json('style_overrides')->nullable()->after('mic_visible');
            $table->string('empty_bg_color', 7)->nullable()->after('style_overrides');
        });

        Schema::table('project_live_details', function (Blueprint $table) {
            $table->dropColumn(['mic_offset_x', 'mic_offset_y']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_live_details', function (Blueprint $table) {
            $table->dropColumn(['style_overrides', 'empty_bg_color']);
        });

        Schema::table('project_live_details', function (Blueprint $table) {
            $table->integer('mic_offset_x')->nullable()->after('mic_visible');
            $table->integer('mic_offset_y')->nullable()->after('mic_offset_x');
        });
    }
};
