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
        Schema::create('project_live_gift_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_live_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tiktok_gift_id')->constrained('tiktok_gifts')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['project_live_id', 'tiktok_gift_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_live_gift_rules');
    }
};
