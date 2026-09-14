<?php

namespace App\Livewire\ProjectLive;

use App\Models\ProjectLive;
use App\Services\OverlayQueueService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Halaman OBS Browser Source - murni nampilin animasi overlay (video WebM) yang lagi
 * "playing" di antrian project ini (App\Services\OverlayQueueService), TIDAK ada
 * kontrol apa pun. Polling (bukan websocket, konsisten dgn mekanisme live lain di app
 * ini) buat: (1) nangkep item BARU begitu masuk antrian selagi belum ada yang tampil,
 * (2) safety net kalau JS client gagal lapor selesai (mis. tab di-reload di tengah
 * animasi, autoplay diblokir) - lihat OverlayQueueService::STUCK_AFTER_SECONDS.
 */
#[Layout('layouts.frame')]
#[Title('Show Animasi Overlay')]
class OverlayShow extends Component
{
    public ProjectLive $projectLive;

    /**
     * @var array{id:int, url:string, duration_ms:int, duration_mode:string}|null
     */
    public ?array $current = null;

    public function mount(ProjectLive $projectLive): void
    {
        $this->authorize('viewLive', $projectLive);

        $this->projectLive = $projectLive;
        $this->syncCurrent();
    }

    /**
     * Dipanggil wire:poll - SELALU sinkron ulang ke DB (bukan cuma pas $current
     * kosong) supaya safety net item "playing" yang macet (OverlayQueueService::
     * currentOrNext()) benar2 kepakai - query ini idempoten selama item yang sama
     * masih "playing" (balik nilai yang SAMA persis, tidak restart videonya krn
     * wire:key di blade tetap sama).
     */
    public function poll(): void
    {
        $this->syncCurrent();
    }

    /**
     * Dipanggil JS begitu animasi dianggap selesai - via event "ended" bawaan
     * <video> (mode "auto", App\Models\OverlayAnimation::duration_mode) atau via
     * setTimeout sepanjang duration_ms (mode "manual") - lihat overlay-show.blade.php.
     */
    public function finishCurrent(): void
    {
        if (! $this->current) {
            return;
        }

        $item = $this->projectLive->overlayQueueItems()->find($this->current['id']);

        if ($item) {
            app(OverlayQueueService::class)->finish($item);
        }

        $this->current = null;
        $this->syncCurrent();
    }

    private function syncCurrent(): void
    {
        $item = app(OverlayQueueService::class)->currentOrNext($this->projectLive);

        $this->current = $item ? [
            'id' => $item->id,
            'url' => $item->overlayAnimation->fileUrl(),
            'duration_ms' => $item->overlayAnimation->duration_ms,
            'duration_mode' => $item->overlayAnimation->duration_mode,
        ] : null;
    }

    public function render()
    {
        return view('livewire.project-live.overlay-show');
    }
}
