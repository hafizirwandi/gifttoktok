<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Animasi overlay pilihan admin utk satu Event Trigger (join/follow/dst) - boleh
     * lebih dari satu, salah satunya dipilih ACAK tiap kali trigger-nya kena
     * (App\Services\OverlayQueueService::enqueueRandom(), dipanggil dari
     * App\Services\EventTriggerProcessor::handle()). Opsional - trigger boleh tidak
     * punya animasi sama sekali (cuma efek gift/leaderboard biasa).
     */
    public function up(): void
    {
        // Nama constraint FK/unique di-persingkat manual - default auto-generate
        // Laravel kepanjangan dari batas 64 karakter identifier MySQL (error 1059)
        // kalau nama tabel+kolomnya digabung apa adanya.
        Schema::create('project_live_event_trigger_overlay_animations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_live_event_trigger_id');
            $table->unsignedBigInteger('overlay_animation_id');
            $table->timestamps();

            $table->foreign('project_live_event_trigger_id', 'plet_overlay_trigger_fk')
                ->references('id')->on('project_live_event_triggers')->cascadeOnDelete();

            $table->foreign('overlay_animation_id', 'plet_overlay_animation_fk')
                ->references('id')->on('overlay_animations')->cascadeOnDelete();

            $table->unique(['project_live_event_trigger_id', 'overlay_animation_id'], 'plet_overlay_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_live_event_trigger_overlay_animations');
    }
};
