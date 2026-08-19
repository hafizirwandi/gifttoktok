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
        Schema::create('project_live_color_hotkeys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_live_id')->constrained()->cascadeOnDelete();
            $table->char('hotkey', 1);
            $table->string('color', 7);
            $table->timestamps();

            $table->unique(['project_live_id', 'hotkey']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_live_color_hotkeys');
    }
};
