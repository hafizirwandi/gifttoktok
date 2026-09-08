<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pendamping empty_icon_offset_y/empty_label_offset_y yang sudah ada (cuma Y) -
     * lengkapi jadi bisa geser kiri/kanan juga, sama pola dgn coin/name/mic/gift_badge.
     */
    public function up(): void
    {
        Schema::table('project_lives', function (Blueprint $table) {
            $table->integer('empty_icon_offset_x')->default(0)->after('empty_icon_offset_y');
            $table->integer('empty_label_offset_x')->default(0)->after('empty_label_offset_y');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_lives', function (Blueprint $table) {
            $table->dropColumn(['empty_icon_offset_x', 'empty_label_offset_x']);
        });
    }
};
