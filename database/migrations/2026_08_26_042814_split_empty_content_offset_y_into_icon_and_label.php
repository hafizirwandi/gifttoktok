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
        Schema::table('project_lives', function (Blueprint $table) {
            $table->integer('empty_icon_offset_y')->default(0)->after('empty_content_offset_y');
            $table->integer('empty_label_offset_y')->default(0)->after('empty_icon_offset_y');
        });

        // Backfill: project yang sudah pernah atur offset gabungan sebelumnya,
        // nilainya dipindah ke KEDUANYA (icon & label) supaya tampilan tidak
        // berubah - admin bisa pisahkan lagi manual kalau perlu sesudah ini.
        DB::table('project_lives')->where('empty_content_offset_y', '!=', 0)
            ->update([
                'empty_icon_offset_y' => DB::raw('empty_content_offset_y'),
                'empty_label_offset_y' => DB::raw('empty_content_offset_y'),
            ]);

        Schema::table('project_lives', function (Blueprint $table) {
            $table->dropColumn('empty_content_offset_y');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_lives', function (Blueprint $table) {
            $table->integer('empty_content_offset_y')->default(0)->after('seat_gap');
        });

        DB::table('project_lives')->update([
            'empty_content_offset_y' => DB::raw('empty_icon_offset_y'),
        ]);

        Schema::table('project_lives', function (Blueprint $table) {
            $table->dropColumn(['empty_icon_offset_y', 'empty_label_offset_y']);
        });
    }
};
