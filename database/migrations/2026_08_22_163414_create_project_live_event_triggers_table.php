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
        Schema::create('project_live_event_triggers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_live_id')->constrained()->cascadeOnDelete();
            // join|share|follow|subscribe|like|chat_any|chat_command|gift — lihat App\Enums\EventTriggerType.
            $table->string('type');
            // Gift yang "muncul" (ikon + nilai coin dipakai) kalau trigger ini kena — kosong
            // khusus type=gift (itu cuma saklar on/off utk pipeline gift asli, lihat
            // TikTokGiftEventProcessor).
            $table->foreignId('mapped_gift_id')->nullable()->constrained('tiktok_gifts')->nullOnDelete();
            // Cuma diisi utk type=chat_command — dicocokkan sbg substring (bukan exact match)
            // ke isi chat, case-insensitive.
            $table->string('command_text')->nullable();
            // Cuma diisi utk type=like — jumlah tap minimal dalam satu event WebcastLikeMessage.
            $table->unsignedInteger('min_count')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_live_event_triggers');
    }
};
