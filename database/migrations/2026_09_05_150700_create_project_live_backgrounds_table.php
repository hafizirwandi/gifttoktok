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
        Schema::create('project_live_backgrounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_live_id')->constrained('project_lives')->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->enum('type', ['image', 'video']);
            $table->enum('placement', ['screen', 'seat']);
            $table->unsignedTinyInteger('seat_position')->nullable();
            $table->string('file');
            $table->enum('fit_mode', ['cover', 'contain', 'stretch'])->default('cover');
            $table->integer('offset_x')->default(0);
            $table->integer('offset_y')->default(0);
            $table->unsignedSmallInteger('scale')->default(100);
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_live_backgrounds');
    }
};
