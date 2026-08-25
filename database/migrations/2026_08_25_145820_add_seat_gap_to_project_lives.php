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
            // Default 12 = sama persis dgn class Tailwind gap-3 (0.75rem) yg sebelumnya
            // hardcode di live-show.blade.php/preview-live.blade.php, supaya project
            // lama tidak berubah tampilan.
            $table->unsignedTinyInteger('seat_gap')->default(12)->after('seat_border_radius');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_lives', function (Blueprint $table) {
            $table->dropColumn('seat_gap');
        });
    }
};
