<?php

namespace App\Livewire\ProjectLive;

use App\Enums\DisplayMode;
use App\Enums\ProjectLiveStatus;
use App\Models\ProjectLive;
use App\Models\TikTokGift;
use App\Services\GiftLeaderboardService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Admin - Project Live')]
class DetailAdmin extends Component
{
    use WithFileUploads;

    public ProjectLive $projectLive;

    public string $tiktokUsername = '';

    public string $giftSearch = '';

    public ?int $editingGiftId = null;

    public string $giftDiamondCount = '';

    public bool $showCustomGiftForm = false;

    public string $customGiftName = '';

    public string $customGiftDiamondCount = '0';

    public string $customGiftIconMode = 'upload';

    public $customGiftIcon = null;

    public string $customGiftIconUrl = '';

    /**
     * Elemen kotak kursi Live yang ukurannya bisa diatur admin (persen, 100 = default),
     * lihat kolom *_size di project_lives — diterapkan lewat transform:scale di
     * partials/seat-box.blade.php.
     */
    public const SIZE_FIELDS = ['coin', 'name', 'avatar', 'empty_icon', 'empty_label', 'gift_badge', 'mic'];

    public array $sizes = [];

    /**
     * Padding, tebal border, & rounded border kotak kursi - beda dari SIZE_FIELDS
     * (persen skala 50-200%, transform:scale) krn ini nilai PIXEL literal (bukan
     * skala relatif), makanya dipisah jadi konstanta + properti sendiri. Kunci
     * array = nama kolom project_lives PERSIS (bukan disingkat spt SIZE_FIELDS),
     * value = [min, max, default dalam px] - dipakai bareng buat validasi &
     * reset (lihat saveBoxStyle()/resetBoxStyle()), diterapkan lewat inline
     * style padding/border-width/border-radius di partials/seat-box.blade.php.
     */
    public const BOX_STYLE_FIELDS = [
        'seat_padding' => ['label' => 'Padding Kotak', 'min' => 0, 'max' => 40, 'default' => 0],
        'seat_border_width' => ['label' => 'Tebal Border', 'min' => 0, 'max' => 20, 'default' => 4],
        'seat_border_radius' => ['label' => 'Rounded Border', 'min' => 0, 'max' => 40, 'default' => 12],
        'seat_gap' => ['label' => 'Jarak Antar Kotak', 'min' => 0, 'max' => 40, 'default' => 12],
        // Negatif = naik, positif = turun - cuma menggeser icon+teks kotak KOSONG
        // (empty_icon + empty_label), bukan avatar/nama/coin kursi yang sudah terisi.
        'empty_content_offset_y' => ['label' => 'Naik/Turun Icon & Teks Kotak Kosong', 'min' => -100, 'max' => 100, 'default' => 0],
    ];

    public array $boxStyle = [];

    /**
     * Hotkey yang dipencet di halaman LIVE (bukan di sini) buat langsung memicu Reset
     * Leaderboard/Reset Coin tanpa pindah tab — lihat LiveShow::triggerResetLeaderboard()/
     * triggerResetCoins() dan live-show.blade.php.
     */
    public string $resetLeaderboardHotkey = '';

    public string $resetCoinHotkey = '';

    public function mount(ProjectLive $projectLive): void
    {
        $this->authorize('viewLive', $projectLive);

        $this->projectLive = $projectLive;
        $this->tiktokUsername = (string) $projectLive->tiktok_username;
        $this->resetLeaderboardHotkey = (string) $projectLive->reset_leaderboard_hotkey;
        $this->resetCoinHotkey = (string) $projectLive->reset_coin_hotkey;

        foreach (self::SIZE_FIELDS as $field) {
            $this->sizes[$field] = $projectLive->{$field.'_size'};
        }

        foreach (self::BOX_STYLE_FIELDS as $field => $config) {
            $this->boxStyle[$field] = $projectLive->{$field};
        }
    }

    public function saveSizes(): void
    {
        // Beda dari display_mode (superadmin only) — ukuran konten ini boleh diatur
        // akun role "live" yang di-assign ke project ini juga, sama seperti mereka
        // boleh edit kursi & katalog gift.
        $this->authorize('viewLive', $this->projectLive);

        $rules = collect(self::SIZE_FIELDS)
            ->mapWithKeys(fn ($field) => ["sizes.{$field}" => 'required|integer|min:50|max:200'])
            ->all();

        $validated = $this->validate($rules);

        $data = [];

        foreach (self::SIZE_FIELDS as $field) {
            $data["{$field}_size"] = $validated['sizes'][$field];
        }

        $this->projectLive->update($data);
        $this->projectLive->refresh();

        $this->dispatch('notify', message: 'Ukuran konten berhasil disimpan.');
    }

    public function resetSizes(): void
    {
        $this->authorize('viewLive', $this->projectLive);

        foreach (self::SIZE_FIELDS as $field) {
            $this->sizes[$field] = 100;
        }

        $this->saveSizes();
    }

public function saveBoxStyle(): void
    {
        $this->authorize('viewLive', $this->projectLive);

        $rules = collect(self::BOX_STYLE_FIELDS)
            ->mapWithKeys(fn ($config, $field) => ["boxStyle.{$field}" => "required|integer|min:{$config['min']}|max:{$config['max']}"])
            ->all();

        $validated = $this->validate($rules);

        $this->projectLive->update($validated['boxStyle']);
        $this->projectLive->refresh();

        $this->dispatch('notify', message: 'Padding & border kotak berhasil disimpan.');
    }

    public function resetBoxStyle(): void
    {
        $this->authorize('viewLive', $this->projectLive);

        foreach (self::BOX_STYLE_FIELDS as $field => $config) {
            $this->boxStyle[$field] = $config['default'];
        }

        $this->saveBoxStyle();
    }

    public function toggleProjectLiveStatus(): void
    {
        // Hanya superadmin yang boleh menyalakan/mematikan status live project ini.
        $this->authorize('manage', ProjectLive::class);

        $this->projectLive->update([
            'status' => $this->projectLive->status === ProjectLiveStatus::Live
                ? ProjectLiveStatus::Off->value
                : ProjectLiveStatus::Live->value,
        ]);

        $this->projectLive->refresh();
    }

    public function updateDisplayMode(string $mode): void
    {
        // Cuma superadmin — ini mengubah tampilan halaman Live buat semua penonton,
        // bukan sekadar data kursi.
        $this->authorize('manage', ProjectLive::class);

        $this->projectLive->update([
            'display_mode' => DisplayMode::from($mode)->value,
        ]);

        $this->projectLive->refresh();

        // Ganti tata letak = reset leaderboard sekalian (kursi dikosongkan,
        // round_reset_at dicatat — lihat GiftLeaderboardService::reset()) DAN
        // jumlah kursi disamakan dgn tata letak baru (bisa beda, mis. Layar
        // Penuh cuma 1 kursi — kursi yg posisinya di luar jumlah baru DIHAPUS
        // permanen beserta hotkey/warna kustomnya, ikut kehapus lewat FK
        // cascade). Peringatan destruktifnya sudah muncul di blade lewat
        // wire:confirm SEBELUM method ini dipanggil.
        app(GiftLeaderboardService::class)->reset($this->projectLive);
        $this->projectLive->syncDetailsToDisplayMode();
    }

    public function toggleAutoGiftMode(): void
    {
        $this->authorize('viewLive', $this->projectLive);

        $data = ['auto_gift_mode' => ! $this->projectLive->auto_gift_mode];

        if ($data['auto_gift_mode'] && ! $this->projectLive->webhook_secret) {
            $data['webhook_secret'] = Str::random(40);
        }

        $this->projectLive->update($data);
        $this->projectLive->refresh();
    }

    public function saveTikTokUsername(): void
    {
        $this->authorize('viewLive', $this->projectLive);

        $validated = $this->validate([
            'tiktokUsername' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[a-zA-Z0-9._]+$/',
                Rule::unique('project_lives', 'tiktok_username')->ignore($this->projectLive->id),
            ],
        ]);

        $this->projectLive->update(['tiktok_username' => $validated['tiktokUsername'] ?: null]);

        $this->dispatch('notify', message: 'Username TikTok berhasil disimpan.');
    }

    public function resetLeaderboard(): void
    {
        $this->authorize('viewLive', $this->projectLive);

        app(GiftLeaderboardService::class)->reset($this->projectLive);

        $this->dispatch('notify', message: 'Leaderboard berhasil direset.');
    }

    public function resetCoins(): void
    {
        $this->authorize('viewLive', $this->projectLive);

        app(GiftLeaderboardService::class)->resetCoins($this->projectLive);

        $this->dispatch('notify', message: 'Semua coin berhasil direset ke 0.');
    }

    public function saveResetHotkeys(): void
    {
        $this->authorize('viewLive', $this->projectLive);

        $validated = $this->validate([
            'resetLeaderboardHotkey' => [
                'nullable',
                'string',
                'size:1',
                // "different" (bukan Rule::notIn) sengaja dibandingkan case-insensitive di
                // closure di bawah, supaya "R" vs "r" tetap dianggap tabrakan.
                function ($attribute, $value, $fail) {
                    if ($value !== '' && $this->resetCoinHotkey !== '' && strtolower($value) === strtolower($this->resetCoinHotkey)) {
                        $fail('Hotkey ini sudah dipakai sebagai hotkey Reset Coin di bawah — pilih huruf/angka lain.');

                        return;
                    }

                    $conflict = $this->projectLive->findHotkeyConflict($value, 'reset_leaderboard');

                    if ($conflict) {
                        $fail("Hotkey ini sudah dipakai sebagai {$conflict} — pilih huruf/angka lain.");
                    }
                },
            ],
            'resetCoinHotkey' => [
                'nullable',
                'string',
                'size:1',
                function ($attribute, $value, $fail) {
                    $conflict = $this->projectLive->findHotkeyConflict($value, 'reset_coin');

                    if ($conflict) {
                        $fail("Hotkey ini sudah dipakai sebagai {$conflict} — pilih huruf/angka lain.");
                    }
                },
            ],
        ]);

        $this->projectLive->update([
            'reset_leaderboard_hotkey' => $validated['resetLeaderboardHotkey'] !== '' ? strtolower($validated['resetLeaderboardHotkey']) : null,
            'reset_coin_hotkey' => $validated['resetCoinHotkey'] !== '' ? strtolower($validated['resetCoinHotkey']) : null,
        ]);

        $this->projectLive->refresh();

        $this->dispatch('notify', message: 'Hotkey reset berhasil disimpan.');
    }

    public function toggleGiftRule(int $tiktokGiftId): void
    {
        $this->authorize('viewLive', $this->projectLive);

        if ($this->projectLive->enabledGifts()->where('tiktok_gifts.id', $tiktokGiftId)->exists()) {
            $this->projectLive->enabledGifts()->detach($tiktokGiftId);
        } else {
            $this->projectLive->enabledGifts()->attach($tiktokGiftId);
        }
    }

    /**
     * Aktifkan/nonaktifkan semua gift yang SEDANG TAMPIL di list (mengikuti filter
     * pencarian `giftSearch` yang aktif) — bukan selalu seluruh katalog.
     */
    public function enableAllGifts(): void
    {
        $this->authorize('viewLive', $this->projectLive);

        $this->projectLive->enabledGifts()->syncWithoutDetaching($this->filteredGiftIds());
    }

    public function disableAllGifts(): void
    {
        $this->authorize('viewLive', $this->projectLive);

        $this->projectLive->enabledGifts()->detach($this->filteredGiftIds());
    }

    /**
     * Edit/hapus gift di sini menyentuh katalog GLOBAL (tabel tiktok_gifts), dipakai
     * bersama oleh semua project — jadi bisa berdampak ke project lain juga, tapi
     * tetap dibolehkan buat akun role "live" yang di-assign ke project ini (sama
     * seperti toggleGiftRule() dkk), bukan cuma superadmin.
     */
    public function openEditGiftDiamond(int $giftId): void
    {
        $this->authorize('viewLive', $this->projectLive);

        $gift = TikTokGift::findOrFail($giftId);

        $this->editingGiftId = $gift->id;
        $this->giftDiamondCount = (string) $gift->diamond_count;
    }

    public function cancelEditGiftDiamond(): void
    {
        $this->reset(['editingGiftId', 'giftDiamondCount']);
    }

    public function saveGiftDiamond(): void
    {
        $this->authorize('viewLive', $this->projectLive);

        $validated = $this->validate([
            'giftDiamondCount' => 'required|integer|min:0',
        ]);

        TikTokGift::whereKey($this->editingGiftId)->update([
            'diamond_count' => $validated['giftDiamondCount'],
        ]);

        $this->reset(['editingGiftId', 'giftDiamondCount']);

        $this->dispatch('notify', message: 'Nilai coin gift berhasil disimpan.');
    }

    public function deleteGift(int $giftId): void
    {
        $this->authorize('viewLive', $this->projectLive);

        TikTokGift::whereKey($giftId)->delete();

        $this->dispatch('notify', message: 'Gift berhasil dihapus dari katalog.');
    }

    public function openCustomGiftForm(): void
    {
        $this->authorize('viewLive', $this->projectLive);

        $this->showCustomGiftForm = true;
    }

    public function closeCustomGiftForm(): void
    {
        $this->reset(['showCustomGiftForm', 'customGiftName', 'customGiftDiamondCount', 'customGiftIconMode', 'customGiftIcon', 'customGiftIconUrl']);
    }

    /**
     * Gift buatan user sendiri (bukan dari katalog resmi TikTok) — ikonnya bisa diupload
     * langsung atau cukup ditaruh link gambarnya. Otomatis diaktifkan buat project ini
     * juga supaya langsung kepakai tanpa langkah tambahan.
     */
    public function saveCustomGift(): void
    {
        $this->authorize('viewLive', $this->projectLive);

        $validated = $this->validate([
            'customGiftName' => 'required|string|max:255',
            'customGiftDiamondCount' => 'required|integer|min:0',
            'customGiftIcon' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
            'customGiftIconUrl' => 'nullable|url|max:2048',
        ]);

        $iconUrl = null;

        if ($this->customGiftIcon) {
            $path = $this->customGiftIcon->store('tiktok-gifts/custom', 'public');
            $iconUrl = Storage::disk('public')->url($path);
        } elseif ($validated['customGiftIconUrl'] !== '') {
            $iconUrl = $validated['customGiftIconUrl'];
        }

        $gift = TikTokGift::create([
            'tiktok_gift_id' => 'custom-'.Str::uuid(),
            'name' => $validated['customGiftName'],
            'diamond_count' => $validated['customGiftDiamondCount'],
            'icon_url' => $iconUrl,
            'is_custom' => true,
        ]);

        $this->projectLive->enabledGifts()->attach($gift->id);

        $this->closeCustomGiftForm();

        $this->dispatch('notify', message: 'Gift custom berhasil ditambahkan & langsung aktif buat project ini.');
    }

    private function filteredGiftsQuery()
    {
        return TikTokGift::query()
            ->when($this->giftSearch, fn ($q) => $q->where('name', 'like', '%'.$this->giftSearch.'%'));
    }

    private function filteredGiftIds(): array
    {
        return $this->filteredGiftsQuery()->pluck('id')->all();
    }

    /**
     * Katalog gift sekarang 10rb+ baris (bukan ~600 seperti dulu) — list di admin JANGAN
     * pernah nge-render semuanya sekaligus (halaman ini polling tiap 5 detik saat Auto
     * Gift Mode nyala), jadi selalu dibatasi. "Aktifkan/Nonaktifkan Semua" tetap bisa
     * kena SEMUA hasil filter (bukan cuma yang tampil) karena itu cuma 1 query UPDATE,
     * bukan render — lihat filteredGiftIds().
     */
    private const GIFT_LIST_DISPLAY_LIMIT = 100;

    public function render()
    {
        return view('livewire.project-live.detail-admin', [
            'gifts' => $this->filteredGiftsQuery()->orderByDesc('diamond_count')->limit(self::GIFT_LIST_DISPLAY_LIMIT)->get(),
            'giftMatchCount' => $this->filteredGiftsQuery()->count(),
            'enabledGiftIds' => $this->projectLive->enabledGifts()->pluck('tiktok_gifts.id')->all(),
            'giftCatalogCount' => TikTokGift::count(),
            // ::max() adalah agregat mentah (bukan hasil hydrate model), jadi TIDAK melalui
            // cast atribut model — hasilnya string, bukan Carbon, harus di-parse manual.
            'giftCatalogUpdatedAt' => ($max = TikTokGift::max('updated_at')) ? \Carbon\Carbon::parse($max) : null,
        ]);
    }
}
