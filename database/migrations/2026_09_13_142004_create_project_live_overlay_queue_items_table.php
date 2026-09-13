<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Antrian animasi overlay per project - App\Services\TikTokGiftEventProcessor
     * (gift asli) & App\Services\EventTriggerProcessor (join/follow/dst) SAMA-SAMA
     * cuma menambah baris "pending" ke sini (App\Services\OverlayQueueService::
     * enqueueRandom()) - App\Livewire\ProjectLive\OverlayShow (halaman OBS Browser
     * Source) yang memproses antrian ini lewat polling, satu per satu FIFO, TIDAK
     * pernah lebih dari satu "playing" bersamaan per project.
     */
    public function up(): void
    {
        Schema::create('project_live_overlay_queue_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_live_id')->constrained()->cascadeOnDelete();
            $table->foreignId('overlay_animation_id')->constrained('overlay_animations')->cascadeOnDelete();
            // pending -> playing -> done, lihat App\Services\OverlayQueueService.
            $table->string('status')->default('pending');
            $table->timestamp('played_at')->nullable();
            $table->timestamps();

            $table->index(['project_live_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_live_overlay_queue_items');
    }
};
