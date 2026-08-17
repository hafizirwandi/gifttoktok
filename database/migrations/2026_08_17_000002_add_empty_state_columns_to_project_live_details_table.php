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
        Schema::table('project_live_details', function (Blueprint $table) {
            // Teks & ikon (emoji) kustom yang tampil di kotak kursi yang masih kosong
            // di layar Live — null berarti pakai default ("Request" + "+").
            $table->string('empty_label')->nullable()->after('dominant_color');
            $table->string('empty_icon', 8)->nullable()->after('empty_label');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_live_details', function (Blueprint $table) {
            $table->dropColumn(['empty_label', 'empty_icon']);
        });
    }
};
