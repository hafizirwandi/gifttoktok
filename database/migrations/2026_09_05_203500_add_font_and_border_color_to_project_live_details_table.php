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
            // Font teks kotak kosong (empty_label) - null = pakai default (Figtree).
            $table->string('font')->nullable()->after('empty_label');
            // Warna border kotak - null = pakai default (border-white/15 bawaan
            // seat-box.blade.php), hex custom kalau admin override lewat Preview Live.
            $table->string('border_color', 9)->nullable()->after('font');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_live_details', function (Blueprint $table) {
            $table->dropColumn(['font', 'border_color']);
        });
    }
};
