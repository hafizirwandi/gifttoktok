<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // display_mode disederhanakan jadi cuma 2 pilihan: kotak host dihapus total dari
        // kedua mode, jadi "landscape" & "landscape_2host" lama sama-sama jadi "horizontal",
        // dan "portrait" lama jadi "vertical".
        DB::table('project_lives')->where('display_mode', 'portrait')->update(['display_mode' => 'vertical']);
        DB::table('project_lives')->whereIn('display_mode', ['landscape', 'landscape_2host'])->update(['display_mode' => 'horizontal']);

        Schema::table('project_lives', function (Blueprint $table) {
            $table->string('display_mode')->default('vertical')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('project_lives')->where('display_mode', 'vertical')->update(['display_mode' => 'portrait']);
        DB::table('project_lives')->where('display_mode', 'horizontal')->update(['display_mode' => 'landscape']);

        Schema::table('project_lives', function (Blueprint $table) {
            $table->string('display_mode')->default('portrait')->change();
        });
    }
};
