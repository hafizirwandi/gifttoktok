<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tebal border LOKAL kotak ini saja - null (default) = ikut GLOBAL
     * (project_lives.seat_border_width, Admin -> "Padding, Border & Jarak Kotak").
     * Satu toggle dgn border_color yang sudah ada ("Border Kotak" di panel Custom
     * Preview Live) - keduanya bagian dari 1 override "Border Kotak Ini".
     */
    public function up(): void
    {
        Schema::table('project_live_details', function (Blueprint $table) {
            $table->unsignedTinyInteger('border_width')->nullable()->after('border_color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_live_details', function (Blueprint $table) {
            $table->dropColumn('border_width');
        });
    }
};
