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
     * Bentuk tiap elemen array ini lihat App\Models\ProjectLiveDetail::toLiveArray()
     * (dipakai bareng oleh App\Livewire\ProjectLive\PreviewLive juga).
     *
     * @var array<int, array<string, mixed>>
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

    /**
     * BG layar penuh yang lagi aktif (lihat App\Livewire\ProjectLive\Background) -
     * null kalau tidak ada. Disimpan sbg array biasa (bukan Eloquent) sama alasannya
     * dgn $details - lihat loadScreenBackground().
     *
     * @var array{type:string, url:string, fit_mode:string, offset_x:int, offset_y:int, scale:int}|null
     */
    public ?array $screenBackground = null;

    public function mount(ProjectLive $projectLive): void
    {
        $this->authorize('viewLive', $projectLive);

        $this->projectLive = $projectLive;
        $this->details = $this->projectLive->details()
            ->with('background')
            ->orderBy('position')
            ->get()
            ->map(fn (ProjectLiveDetail $detail) => $detail->toLiveArray())
            ->all();

        $this->syncColorHotkeys();
        $this->loadScreenBackground();
    }

    private function loadScreenBackground(): void
    {
        $bg = $this->projectLive->activeScreenBackground();

        $this->screenBackground = $bg ? $bg->toLiveArray() : null;
    }

    /**
     * Dipanggil Livewire setiap kali komponen di-restore dari snapshot browser (setiap
     * request SELAIN load pertama/mount). Tab Live yang sudah lama terbuka bisa bawa
     * snapshot $details versi lama yang bentuk arraynya belum punya key yang baru
     * ditambahkan ke ProjectLiveDetail::toLiveArray() (mis. active_hotkey_color) — tanpa ini, seat-box.blade.php
     * bakal lempar "Undefined array key" begitu ada request (polling/klik) dan bikin
     * halaman Live error 500 sampai operator refresh manual. Backfill key yang hilang
     * dengan default supaya tab lama tetap jalan normal sampai halaman di-reload.
     */
    public function hydrate(): void
    {
        $defaults = [
            'name' => null,
            'hotkey' => null,
            'status' => DetailStatus::Hide->value,
            'is_pinned' => false,
            'dominant_color' => '#111111',
            'img_url' => null,
            'source' => null,
            'gift_total_value' => 0,
            'empty_label' => null,
            'empty_icon_url' => null,
            'font' => null,
            'border_color' => null,
            'mic_visible' => true,
            'background' => null,
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
            ->with('background')
            ->orderBy('position')
            ->get()
            ->map(fn (ProjectLiveDetail $detail) => $detail->toLiveArray())
            ->all();
    }

    /**
     * Hotkey Reset Coin — sama seperti di atas, langsung jalan tanpa konfirmasi.
     */
    public function triggerResetCoins(): void
    {
        app(GiftLeaderboardService::class)->resetCoins($this->projectLive);

        $this->details = $this->projectLive->details()
            ->with('background')
            ->orderBy('position')
            ->get()
            ->map(fn (ProjectLiveDetail $detail) => $detail->toLiveArray())
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
                $this->details[$i] = $dbDetail->toLiveArray();
                break;
            }
        }
    }

    /**
     * Dipanggil berkala oleh wire:poll. DB sepenuhnya otoritatif — status show/hide
     * (dan semua field lain) langsung disinkronkan penuh dari DB tiap tick, DUA ARAH
     * (show maupun hide), berapa pun mode-nya (auto_gift_mode atau manual). Hotkey/klik
     * kursi (toggleByHotkey/toggleClick di bawah) tetap ada sebagai jalan pintas supaya
     * operator tidak perlu menunggu tick poll berikutnya.
     */
    public function syncFromDatabase(): void
    {
        $this->projectLive->refresh();
        $this->syncColorHotkeys();
        $this->loadScreenBackground();

        $this->details = $this->projectLive->details()
            ->with('background')
            ->orderBy('position')
            ->get()
            ->map(fn (ProjectLiveDetail $detail) => $detail->toLiveArray())
            ->all();
    }

    public function render()
    {
        return view('livewire.project-live.live-show');
    }
}
