<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * empty_icon dulu menyimpan KARAKTER EMOJI (dipilih dari daftar tetap di
     * App\Livewire\ProjectLive\PreviewLive::EMPTY_ICON_CHOICES) - sekarang kolom yang
     * sama dipakai buat menyimpan PATH GAMBAR hasil upload admin (lihat
     * ProjectLiveDetail::emptyIconUrl()). Nilai emoji lama tidak valid sbg path gambar
     * (bakal jadi broken-image kalau dipaksa render sbg <img src>), jadi dikosongkan di
     * sini - kotak yang kosong balik ke fallback default ('+') sampai admin upload
     * gambar baru lewat Preview Live.
     */
    public function up(): void
    {
        DB::table('project_live_details')->update(['empty_icon' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nilai emoji lama tidak disimpan di mana pun sebelum migration ini jalan -
        // tidak ada apa pun yang bisa dikembalikan.
    }
};
