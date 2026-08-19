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
            // Skala ukuran (persen, 100 = default) tiap elemen di kotak kursi halaman
            // Live — admin bisa perbesar/perkecil, diterapkan lewat CSS transform:scale
            // di seat-box.blade.php. coin_size/name_size men-skala SELURUH badge
            // (background + isi), bukan cuma teksnya.
            $table->unsignedTinyInteger('coin_size')->default(100)->after('display_mode');
            $table->unsignedTinyInteger('name_size')->default(100)->after('coin_size');
            $table->unsignedTinyInteger('avatar_size')->default(100)->after('name_size');
            $table->unsignedTinyInteger('empty_icon_size')->default(100)->after('avatar_size');
            $table->unsignedTinyInteger('empty_label_size')->default(100)->after('empty_icon_size');
            $table->unsignedTinyInteger('gift_badge_size')->default(100)->after('empty_label_size');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_lives', function (Blueprint $table) {
            $table->dropColumn([
                'coin_size',
                'name_size',
                'avatar_size',
                'empty_icon_size',
                'empty_label_size',
                'gift_badge_size',
            ]);
        });
    }
};
