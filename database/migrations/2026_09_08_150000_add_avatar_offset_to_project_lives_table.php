<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Naik/turun & geser kiri/kanan foto avatar kursi normal - sebelumnya cuma bisa
     * di-scale (avatar_size), posisinya kaku di tengah kotak (absolute inset-0 +
     * items-center justify-center, tanpa translate). Global, sama pola dgn
     * coin/name/mic_offset_x/y - lihat App\Livewire\ProjectLive\DetailAdmin::
     * BOX_STYLE_FIELDS & partials/seat-box.blade.php.
     */
    public function up(): void
    {
        Schema::table('project_lives', function (Blueprint $table) {
            $table->integer('avatar_offset_x')->default(0)->after('avatar_size');
            $table->integer('avatar_offset_y')->default(0)->after('avatar_offset_x');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_lives', function (Blueprint $table) {
            $table->dropColumn(['avatar_offset_x', 'avatar_offset_y']);
        });
    }
};
