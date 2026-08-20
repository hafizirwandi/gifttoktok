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
        Schema::table('tiktok_gifts', function (Blueprint $table) {
            // Gift yang diinput manual oleh user (bukan dari katalog resmi TikTok) —
            // lihat App\Livewire\ProjectLive\DetailAdmin::saveCustomGift().
            $table->boolean('is_custom')->default(false)->after('icon_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tiktok_gifts', function (Blueprint $table) {
            $table->dropColumn('is_custom');
        });
    }
};
