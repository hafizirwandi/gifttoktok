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
        Schema::table('project_live_gifters', function (Blueprint $table) {
            // total_value = akumulasi lifetime (tidak pernah direset).
            // round_value = akumulasi di "putaran" berjalan saja, direset ke 0 tiap kali
            // 8 kursi penuh & otomatis dikosongkan — dipakai untuk ranking kursi supaya
            // gifter lain juga dapat giliran tiap putaran baru.
            $table->unsignedBigInteger('round_value')->default(0)->after('total_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_live_gifters', function (Blueprint $table) {
            $table->dropColumn('round_value');
        });
    }
};
