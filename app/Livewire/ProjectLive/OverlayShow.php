<?php

namespace App\Livewire\ProjectLive;

use App\Models\ProjectLive;
use App\Services\OverlayQueueService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Halaman OBS Browser Source - murni nampilin animasi overlay yang lagi "playing" di
 * antrian project ini (App\Services\OverlayQueueService), TIDAK ada kontrol apa pun.
 * Polling (bukan websocket, konsisten dgn mekanisme live lain di app ini) buat: (1)
 * nangkep item BARU begitu masuk antrian selagi belum ada yang tampil, (2) safety
 * net kalau JS client gagal lapor selesai (mis. tab di-reload di tengah animasi).
 */
#[Layout('layouts.frame')]
#[Title('Show Animasi Overlay')]
class OverlayShow extends Component
{
    public ProjectLive $projectLive;

    /**
     * @var array{id:int, url:string, duration_ms:int}|null
     */
    public ?array $current = null;

    public function mount(ProjectLive $projectLive): void
    {
        $this->authorize('viewLive', $projectLive);

        $this->projectLive = $projectLive;
        $this->syncCurrent();
    }

    /**
     * Dipanggil wire:poll - hanya benar-benar nge-pop antrian kalau belum ada yang
     * "playing" (lihat OverlayQueueService::currentOrNext()), jadi tidak mengganggu
     * animasi yang sedang tampil.
     */
    public function poll(): void
    {
        $this->syncCurrent();
    }

    /**
     * Dipanggil JS (x-init setTimeout sepanjang duration_ms milik animasi yang lagi
     * tampil, lihat overlay-show.blade.php) begitu animasi WebP-nya dianggap selesai
     * secara visual - animated WebP tidak punya event "ended" resmi spt <video>, jadi
     * timer ini satu-satunya cara tahu kapan harus lanjut.
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
        if ($this->current) {
            return;
        }

        $item = app(OverlayQueueService::class)->currentOrNext($this->projectLive);

        $this->current = $item ? [
            'id' => $item->id,
            'url' => $item->overlayAnimation->fileUrl(),
            'duration_ms' => $item->overlayAnimation->duration_ms,
        ] : null;
    }

    public function render()
    {
        return view('livewire.project-live.overlay-show');
    }
}
