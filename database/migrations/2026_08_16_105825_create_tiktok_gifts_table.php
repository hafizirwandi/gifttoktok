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
        Schema::create('tiktok_gifts', function (Blueprint $table) {
            $table->id();
            $table->string('tiktok_gift_id')->unique();
            $table->string('name');
            $table->unsignedInteger('diamond_count')->default(0);
            $table->string('icon_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tiktok_gifts');
    }
};
