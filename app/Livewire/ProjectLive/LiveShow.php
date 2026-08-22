<?php

namespace App\Livewire\ProjectLive;

use App\Enums\DetailStatus;
use App\Models\ProjectLive;
use App\Models\ProjectLiveDetail;
use App\Services\GiftLeaderboardService;
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
     * @var array<int, array{id:int, position:int, name:?string, hotkey:?string, status:string, dominant_color:string, img_url:?string, empty_label:?string, empty_icon:?string, active_hotkey_color:?string, last_gift_icon_url:?string, last_gift_at:?int, show_gift_badge:bool}>
     */
    public array $details = [];

    /**
     * Daftar hotkey char buat warna global (lihat App\Livewire\ProjectLive\HotkeyColor)
     * — dipakai Alpine di blade buat tahu tombol mana yang harus di-intercept, warna
     * aslinya di-resolve ulang di server lewat activateColorHotkey() supaya tidak
     * percaya begitu saja nilai dari client.
     *
     * @var array<int, string>
     */
    public array $colorHotkeys = [];

    public ?string $defaultColorHotkey = null;

    /**
     * Hotkey Reset Leaderboard/Reset Coin (diatur admin di halaman Admin) — dipencet
     * langsung di sini, TANPA konfirmasi (beda dari tombolnya di Admin yang pakai
     * wire:confirm), supaya operator tidak perlu pindah tab saat siaran. Lihat
     * triggerResetLeaderboard()/triggerResetCoins() & live-show.blade.php.
     */
    public ?string $resetLeaderboardHotkey = null;

    public ?string $resetCoinHotkey = null;

    public function mount(ProjectLive $projectLive): void
    {
        $this->authorize('viewLive', $projectLive);

        $this->projectLive = $projectLive;
        $this->details = $this->projectLive->details()
            ->orderBy('position')
            ->get()
            ->map(fn (ProjectLiveDetail $detail) => $this->toArray($detail))
            ->all();

        $this->syncColorHotkeys();
    }

    /**
     * Dipanggil Livewire setiap kali komponen di-restore dari snapshot browser (setiap
     * request SELAIN load pertama/mount). Tab Live yang sudah lama terbuka bisa bawa
     * snapshot $details versi lama yang bentuk arraynya belum punya key yang baru
     * ditambahkan ke toArray() (mis. active_hotkey_color) — tanpa ini, seat-box.blade.php
     * bakal lempar "Undefined array key" begitu ada request (polling/klik) dan bikin
     * halaman Live error 500 sampai operator refresh manual. Backfill key yang hilang
     * dengan default supaya tab lama tetap jalan normal sampai halaman di-reload.
     */
    public function hydrate(): void
    {
        $defaults = [
            'source' => null,
            'empty_label' => null,
            'empty_icon' => null,
            'active_hotkey_color' => null,
            'last_gift_icon_url' => null,
            'last_gift_at' => null,
            'show_gift_badge' => false,
        ];

        $this->details = array_map(fn (array $detail) => $detail + $defaults, $this->details);
    }

    /**
     * Dipencet operator di Live — cari warna dari hotkey ini. Kalau hotkey-nya GLOBAL
     * (project_live_detail_id null), semua kotak kursi yang masih kosong ikut ganti
     * warna. Kalau hotkey-nya PER-KURSI, cuma kursi itu yang ganti warna (lihat
     * partials/seat-box.blade.php buat urutan prioritasnya). Tidak ngefek kalau
     * hotkey-nya tidak terdaftar.
     */
    public function activateColorHotkey(string $hotkey): void
    {
        $entry = $this->projectLive->colorHotkeys()->where('hotkey', strtolower($hotkey))->first();

        if (! $entry) {
            return;
        }

        if ($entry->project_live_detail_id === null) {
            $this->projectLive->update(['active_hotkey_color' => $entry->color]);
            $this->projectLive->refresh();

            return;
        }

        $this->projectLive->details()->whereKey($entry->project_live_detail_id)->update([
            'active_hotkey_color' => $entry->color,
        ]);

        foreach ($this->details as $i => $detail) {
            if ($detail['id'] === $entry->project_live_detail_id) {
                $this->details[$i]['active_hotkey_color'] = $entry->color;
                break;
            }
        }
    }

    /**
     * Hotkey default (atau tombol "Reset ke Default" di halaman Hotkey Warna) —
     * matikan SEMUA override warna (global maupun per-kursi), kotak kosong balik
     * pakai warna per-kursi statisnya masing-masing.
     */
    public function resetColorHotkey(): void
    {
        $this->projectLive->update(['active_hotkey_color' => null]);
        $this->projectLive->refresh();

        $this->projectLive->details()->update(['active_hotkey_color' => null]);

        foreach ($this->details as $i => $detail) {
            $this->details[$i]['active_hotkey_color'] = null;
        }
    }

    private function syncColorHotkeys(): void
    {
        $this->colorHotkeys = $this->projectLive->colorHotkeys()->pluck('hotkey')->all();
        $this->defaultColorHotkey = $this->projectLive->default_color_hotkey;
        $this->resetLeaderboardHotkey = $this->projectLive->reset_leaderboard_hotkey;
        $this->resetCoinHotkey = $this->projectLive->reset_coin_hotkey;
    }

    /**
     * Hotkey Reset Leaderboard — langsung jalan begitu ditekan (tanpa konfirmasi, lihat
     * penjelasan di properti resetLeaderboardHotkey di atas). $this->details di-refetch
     * penuh sesudahnya supaya operator langsung lihat kursi kosong, tidak perlu nunggu
     * poll berikutnya.
     */
    public function triggerResetLeaderboard(): void
    {
        app(GiftLeaderboardService::class)->reset($this->projectLive);

        $this->details = $this->projectLive->details()
            ->orderBy('position')
            ->get()
            ->map(fn (ProjectLiveDetail $detail) => $this->toArray($detail))
            ->all();
    }

    /**
     * Hotkey Reset Coin — sama seperti di atas, langsung jalan tanpa konfirmasi.
     */
    public function triggerResetCoins(): void
    {
        app(GiftLeaderboardService::class)->resetCoins($this->projectLive);

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
        $this->syncColorHotkeys();

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
            'hotkey' => $detail->hotkey,
            'status' => $detail->status->value,
            'dominant_color' => $detail->dominant_color,
            'img_url' => $detail->imgUrl(),
            'source' => $detail->source->value,
            'gift_total_value' => $detail->gift_total_value,
            'empty_label' => $detail->empty_label,
            'empty_icon' => $detail->empty_icon,
            'active_hotkey_color' => $detail->active_hotkey_color,
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
