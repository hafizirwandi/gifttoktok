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
        Schema::create('project_live_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_live_id')->constrained('project_lives')->cascadeOnDelete();
            $table->unsignedTinyInteger('position');
            $table->string('img')->nullable();
            $table->string('name')->nullable();
            $table->string('follower')->nullable();
            $table->char('hotkey', 1)->nullable();
            $table->enum('status', ['hide', 'show'])->default('hide');
            $table->string('dominant_color', 7)->default('#111111');
            $table->timestamps();

            $table->unique(['project_live_id', 'position']);
            $table->unique(['project_live_id', 'hotkey']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_live_details');
    }
};
