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
        // Index unique-nya dipakai langsung oleh FK constraint (InnoDB mewajibkan ada
        // index di kolom FK), jadi FK-nya harus dilepas dulu sebelum index unique-nya
        // bisa di-drop, baru dipasang lagi — MySQL otomatis bikinkan index biasa
        // (non-unique) buat FK ini begitu constraint-nya ditambahkan lagi.
        Schema::table('tiktok_gifts', function (Blueprint $table) {
            $table->dropForeign(['mapped_to_gift_id']);
        });

        Schema::table('tiktok_gifts', function (Blueprint $table) {
            $table->dropUnique(['mapped_to_gift_id']);
        });

        Schema::table('tiktok_gifts', function (Blueprint $table) {
            $table->foreign('mapped_to_gift_id')->references('id')->on('tiktok_gifts')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tiktok_gifts', function (Blueprint $table) {
            $table->dropForeign(['mapped_to_gift_id']);
        });

        Schema::table('tiktok_gifts', function (Blueprint $table) {
            $table->unique('mapped_to_gift_id');
        });

        Schema::table('tiktok_gifts', function (Blueprint $table) {
            $table->foreign('mapped_to_gift_id')->references('id')->on('tiktok_gifts')->nullOnDelete();
        });
    }
};
