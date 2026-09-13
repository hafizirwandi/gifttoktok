<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pemetaan gift ASLI (tiktok_gifts, GLOBAL) ke animasi overlay - satu gift boleh
     * dipetakan ke LEBIH DARI SATU animasi, salah satunya dipilih ACAK tiap kali gift
     * itu benar-benar diterima (App\Services\TikTokGiftEventProcessor::applyGift(),
     * cuma utk $isRealGiftEvent=true - lihat App\Services\OverlayQueueService).
     * Diatur lewat halaman "Pemetaan Gift" (App\Livewire\ProjectLive\GiftMapping),
     * SAMA seperti mapped_to_gift_id - berlaku bareng semua project.
     */
    public function up(): void
    {
        Schema::create('tiktok_gift_overlay_animations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tiktok_gift_id')->constrained('tiktok_gifts')->cascadeOnDelete();
            $table->foreignId('overlay_animation_id')->constrained('overlay_animations')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['tiktok_gift_id', 'overlay_animation_id'], 'gift_overlay_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tiktok_gift_overlay_animations');
    }
};
