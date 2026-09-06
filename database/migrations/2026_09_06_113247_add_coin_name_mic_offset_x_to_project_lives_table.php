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
            // Geser kiri/kanan (X) - pendamping *_offset_y yang sudah ada (naik/turun,
            // Y saja) buat 4 elemen yang sama: badge coin, badge nama, icon mic, icon
            // gift pemetaan. Negatif = geser kiri, positif = geser kanan - lihat
            // App\Livewire\ProjectLive\DetailAdmin::BOX_STYLE_FIELDS & partials/
            // seat-box.blade.php (transform: translate(x, y)).
            $table->integer('coin_offset_x')->default(0)->after('name_offset_y');
            $table->integer('name_offset_x')->default(0)->after('coin_offset_x');
            $table->integer('mic_offset_x')->default(0)->after('name_offset_x');
            $table->integer('gift_badge_offset_x')->default(0)->after('gift_badge_offset_y');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_lives', function (Blueprint $table) {
            $table->dropColumn(['coin_offset_x', 'name_offset_x', 'mic_offset_x', 'gift_badge_offset_x']);
        });
    }
};
