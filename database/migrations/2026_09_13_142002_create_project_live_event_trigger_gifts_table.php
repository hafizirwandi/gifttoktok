<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ganti project_live_event_triggers.mapped_gift_id (satu gift doang) jadi pivot -
     * satu trigger sekarang boleh punya LEBIH DARI SATU gift, salah satunya dipilih
     * ACAK tiap kali trigger-nya kena (App\Services\EventTriggerProcessor::handle()).
     * Data lama di-backfill dulu ke pivot sebelum kolomnya dihapus, supaya trigger
     * yang sudah ada admin tidak kehilangan setting-annya.
     */
    public function up(): void
    {
        // Nama constraint FK di-persingkat manual (bukan default auto-generate Laravel)
        // - nama tabel+kolom ini kalau digabung otomatis kepanjangan dari batas 64
        // karakter identifier MySQL (error 1059).
        Schema::create('project_live_event_trigger_gifts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_live_event_trigger_id');
            $table->foreignId('tiktok_gift_id')->constrained('tiktok_gifts')->cascadeOnDelete();
            $table->timestamps();

            $table->foreign('project_live_event_trigger_id', 'plet_gifts_trigger_fk')
                ->references('id')->on('project_live_event_triggers')->cascadeOnDelete();

            $table->unique(['project_live_event_trigger_id', 'tiktok_gift_id'], 'plet_gift_unique');
        });

        $now = now();

        DB::table('project_live_event_triggers')
            ->whereNotNull('mapped_gift_id')
            ->get(['id', 'mapped_gift_id'])
            ->each(function ($row) use ($now) {
                DB::table('project_live_event_trigger_gifts')->insert([
                    'project_live_event_trigger_id' => $row->id,
                    'tiktok_gift_id' => $row->mapped_gift_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });

        Schema::table('project_live_event_triggers', function (Blueprint $table) {
            $table->dropForeign(['mapped_gift_id']);
            $table->dropColumn('mapped_gift_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_live_event_triggers', function (Blueprint $table) {
            $table->foreignId('mapped_gift_id')->nullable()->after('type')->constrained('tiktok_gifts')->nullOnDelete();
        });

        DB::table('project_live_event_trigger_gifts')
            ->orderBy('id')
            ->get()
            ->groupBy('project_live_event_trigger_id')
            ->each(function ($rows, $triggerId) {
                DB::table('project_live_event_triggers')
                    ->whereKey($triggerId)
                    ->update(['mapped_gift_id' => $rows->first()->tiktok_gift_id]);
            });

        Schema::dropIfExists('project_live_event_trigger_gifts');
    }
};
