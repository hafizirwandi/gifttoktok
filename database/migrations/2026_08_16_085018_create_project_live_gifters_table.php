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
        Schema::create('project_live_gifters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_live_id')->constrained()->cascadeOnDelete();
            $table->string('tiktok_user_id');
            $table->string('tiktok_unique_id')->nullable();
            $table->string('nickname')->nullable();
            $table->string('avatar_path')->nullable();
            $table->string('dominant_color', 7)->default('#111111');
            $table->unsignedBigInteger('total_value')->default(0);
            $table->unsignedInteger('gift_count')->default(0);
            $table->timestamp('last_gift_at')->nullable();
            $table->timestamps();

            $table->unique(['project_live_id', 'tiktok_user_id']);
            $table->index(['project_live_id', 'total_value']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_live_gifters');
    }
};
