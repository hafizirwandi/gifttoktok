<?php

namespace App\Livewire\ProjectLive;

use App\Enums\DetailStatus;
use App\Models\ProjectLive;
use App\Models\ProjectLiveDetail;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.live')]
#[Title('Live')]
class LiveShow extends Component
{
    public ProjectLive $projectLive;

    /**
     * State kursi yang sedang TAMPIL di layar operator, disimpan sebagai array biasa
     * (bukan Eloquent Collection) supaya TIDAK di-refetch otomatis dari DB oleh Livewire
     * di setiap request — ini penting karena admin men-set status "show" hanya sebagai
     * izin/persiapan; kursi baru benar-benar tampil setelah operator memicu lewat
     * hotkey/klik. Status "hide" dari admin sebaliknya langsung tersinkron otomatis
     * (lihat syncFromDatabase()).
     *
     * @var array<int, array{id:int, position:int, name:?string, follower:?string, hotkey:?string, status:string, dominant_color:string, img_url:?string, empty_label:?string, empty_icon:?string, last_gift_icon_url:?string, last_gift_at:?int, show_gift_badge:bool}>
     */
    public array $details = [];

    public function mount(ProjectLive $projectLive): void
    {
        $this->authorize('viewLive', $projectLive);

        $this->projectLive = $projectLive;
        $this->details = $this->projectLive->details()
            ->orderBy('position')
            ->get()
            ->map(fn (ProjectLiveDetail $detail) => $this->toArray($detail))
            ->all();
    }

    public function toggleByHotkey(int $detailId): void
    {
        $this->reveal($detailId);
    }

    public function toggleClick(int $detailId): void
    {
        $this->reveal($detailId);
    }

    /**
     * Tampilkan kursi ke layar (atau refresh datanya jika sudah tampil) — TAPI hanya
     * jika admin sudah meng-approve (status di DB = show). Kalau di DB masih hide,
     * trigger ini tidak berpengaruh sama sekali.
     */
    private function reveal(int $detailId): void
    {
        if (! $this->projectLive->isLive()) {
            return;
        }

        $dbDetail = $this->projectLive->details()->find($detailId);

        if (! $dbDetail || $dbDetail->status === DetailStatus::Hide) {
            return;
        }

        foreach ($this->details as $i => $detail) {
            if ($detail['id'] === $detailId) {
                $this->details[$i] = $this->toArray($dbDetail);
                break;
            }
        }
    }

    /**
     * Dipanggil berkala oleh wire:poll. Hanya menyinkronkan transisi ke HIDE secara
     * otomatis (kursi yang di-hide dari admin langsung ikut hilang di layar live).
     * Transisi ke SHOW sengaja tidak di-auto-sync di sini, menunggu trigger hotkey/klik.
     */
    public function syncFromDatabase(): void
    {
        $this->projectLive->refresh();

        if ($this->projectLive->auto_gift_mode) {
            // Auto Gift Mode: DB sepenuhnya otoritatif (leaderboard sudah tervalidasi
            // server-side), tidak ada gating "tunggu trigger operator" sama sekali.
            $this->details = $this->projectLive->details()
                ->orderBy('position')
                ->get()
                ->map(fn (ProjectLiveDetail $detail) => $this->toArray($detail))
                ->all();

            return;
        }

        $dbRows = $this->projectLive->details()->get(['id', 'status', 'hotkey'])->keyBy('id');

        foreach ($this->details as $i => $detail) {
            $dbRow = $dbRows->get($detail['id']);

            if (! $dbRow) {
                continue;
            }

            // Hotkey selalu disinkronkan langsung (bukan konten), supaya operator selalu
            // tahu tombol yang benar meski admin baru saja mengubahnya.
            $this->details[$i]['hotkey'] = $dbRow->hotkey;

            if ($dbRow->status === DetailStatus::Hide && $detail['status'] !== DetailStatus::Hide->value) {
                $this->details[$i]['status'] = DetailStatus::Hide->value;
            }
        }
    }

    /**
     * Berapa lama badge ikon gift terakhir tetap tampil di pojok kotak sebelum fade-out
     * otomatis (lihat GiftBadgeVisibility di partials/seat-box.blade.php).
     */
    private const GIFT_BADGE_SECONDS = 8;

    private function toArray(ProjectLiveDetail $detail): array
    {
        return [
            'id' => $detail->id,
            'position' => $detail->position,
            'name' => $detail->name,
            'follower' => $detail->follower,
            'hotkey' => $detail->hotkey,
            'status' => $detail->status->value,
            'dominant_color' => $detail->dominant_color,
            'img_url' => $detail->imgUrl(),
            'source' => $detail->source->value,
            'gift_total_value' => $detail->gift_total_value,
            'empty_label' => $detail->empty_label,
            'empty_icon' => $detail->empty_icon,
            'last_gift_icon_url' => $detail->last_gift_icon_url,
            'last_gift_at' => $detail->last_gift_at?->timestamp,
            'show_gift_badge' => $detail->last_gift_at !== null
                && $detail->last_gift_at->gt(now()->subSeconds(self::GIFT_BADGE_SECONDS)),
        ];
    }

    public function render()
    {
        return view('livewire.project-live.live-show');
    }
}
