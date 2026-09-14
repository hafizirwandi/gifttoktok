<?php

namespace App\Services;

use App\Models\ProjectLive;
use App\Models\ProjectLiveOverlayQueueItem;
use Illuminate\Support\Collection;

/**
 * Antrian animasi overlay per project (App\Livewire\ProjectLive\OverlayShow, halaman
 * OBS Browser Source) - App\Services\TikTokGiftEventProcessor (gift asli) &
 * App\Services\EventTriggerProcessor (join/follow/dst) SAMA-SAMA cuma menambah baris
 * "pending" lewat enqueueRandom(), OverlayShow yang memproses satu-satu FIFO lewat
 * polling (app ini TIDAK pakai websocket/broadcast sama sekali, konsisten dengan
 * mekanisme live lain di app ini seperti hotkey warna/frame host).
 */
class OverlayQueueService
{
    /**
     * Dipanggil dgn koleksi animasi PILIHAN admin (bisa dari App\Models\TikTokGift::
     * overlayAnimations() atau App\Models\ProjectLiveEventTrigger::overlayAnimations())
     * - kalau kosong atau semuanya nonaktif, tidak menambah apa pun (diam-diam, bukan
     * error, krn animasi overlay memang opsional).
     */
    public function enqueueRandom(ProjectLive $projectLive, Collection $animations): void
    {
        $pool = $animations->where('active', true);

        if ($pool->isEmpty()) {
            return;
        }

        ProjectLiveOverlayQueueItem::create([
            'project_live_id' => $projectLive->id,
            'overlay_animation_id' => $pool->random()->id,
            'status' => 'pending',
        ]);
    }

    /**
     * Safety net - kalau item "playing" sudah lebih lama dari ini tapi client TIDAK
     * PERNAH lapor selesai lewat finish() (mis. event "ended" video gagal nyala krn
     * autoplay diblokir, file 404/korup, atau tab OverlayShow-nya ke-reload di
     * tengah), tandai selesai paksa & lanjut ke antrian berikutnya - biar antrian
     * tidak macet permanen nunggu 1 item yang tidak akan pernah lapor.
     */
    private const STUCK_AFTER_SECONDS = 60;

    /**
     * Item yang lagi "playing" (harusnya cuma satu per project), atau POP item
     * "pending" PALING LAMA jadi "playing" kalau belum ada yang jalan. Dipanggil
     * App\Livewire\ProjectLive\OverlayShow tiap poll - begitu ada yang playing,
     * method ini TIDAK pop lagi (biar tidak keselip), nunggu client lapor selesai
     * lewat finish() (KECUALI sudah macet - lihat STUCK_AFTER_SECONDS).
     */
    public function currentOrNext(ProjectLive $projectLive): ?ProjectLiveOverlayQueueItem
    {
        $playing = ProjectLiveOverlayQueueItem::where('project_live_id', $projectLive->id)
            ->where('status', 'playing')
            ->with('overlayAnimation')
            ->oldest('id')
            ->first();

        if ($playing && $playing->played_at?->diffInSeconds(now()) >= self::STUCK_AFTER_SECONDS) {
            $this->finish($playing);
            $playing = null;
        }

        if ($playing) {
            return $playing;
        }

        $next = ProjectLiveOverlayQueueItem::where('project_live_id', $projectLive->id)
            ->where('status', 'pending')
            ->oldest('id')
            ->first();

        if (! $next) {
            return null;
        }

        $next->update(['status' => 'playing', 'played_at' => now()]);
        $next->load('overlayAnimation');

        return $next;
    }

    public function finish(ProjectLiveOverlayQueueItem $item): void
    {
        $item->update(['status' => 'done']);
    }
}
