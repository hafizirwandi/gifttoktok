<?php

namespace App\Livewire\ProjectLive;

use App\Enums\DisplayMode;
use App\Enums\ProjectLiveStatus;
use App\Enums\SeatFillDirection;
use App\Enums\SeatFont;
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
     * Icon mic custom (App\Models\ProjectLive::micIconUrl()) - satu utk SEMUA kotak
     * kursi project ini, menggantikan SVG mic bawaan di partials/seat-box.blade.php
     * kalau di-upload. Ukuran/posisi tetap pakai mic_size/mic_offset_y yang sudah ada.
     */
    public $micIconFile = null;

    /**
     * Icon kotak kosong custom GLOBAL (App\Models\ProjectLive::emptyIconUrl()) -
     * fallback kalau kotak tidak punya icon LOKAL sendiri (diatur lewat tombol
     * "Custom" di Preview Live). Ukuran/posisi tetap pakai empty_icon_size/
     * empty_icon_offset_x/y yang sudah ada.
     */
    public $emptyIconFile = null;

    /**
     * Elemen kotak kursi Live yang ukurannya bisa diatur admin (persen, 100 = default),
     * lihat kolom *_size di project_lives — diterapkan lewat transform:scale di
     * partials/seat-box.blade.php.
     */
    public const SIZE_FIELDS = ['coin', 'name', 'avatar', 'empty_icon', 'empty_label', 'gift_badge', 'mic', 'host_name'];

    /**
     * Label tampilan per elemen SIZE_FIELDS - dipakai buat ngelompokkan UI-nya jadi
     * satu KARTU per elemen (ukuran + geser kiri/kanan + naik/turun sekaligus,
     * bukan 2 grid terpisah kayak sebelumnya) di detail-admin.blade.php. Tiap key
     * di sini otomatis punya pasangan "{key}_offset_x"/"{key}_offset_y" di
     * BOX_STYLE_FIELDS di bawah - itu yang bikin pengelompokan ini bisa generik.
     */
    public const ELEMENT_GROUPS = [
        'coin' => 'Badge Coin',
        'name' => 'Badge Nama',
        'avatar' => 'Foto',
        'gift_badge' => 'Icon Pemetaan Gift',
        'mic' => 'Icon Mic',
        'empty_icon' => 'Icon Kotak Kosong',
        'empty_label' => 'Teks Kotak Kosong',
        'host_name' => 'Nama Host (bold)',
    ];

    public array $sizes = [];

    /**
     * Padding, tebal border, & rounded border kotak kursi - beda dari SIZE_FIELDS
     * (persen skala 50-200%, transform:scale) krn ini nilai PIXEL literal (bukan
     * skala relatif), makanya dipisah jadi konstanta + properti sendiri. Kunci
     * array = nama kolom project_lives PERSIS (bukan disingkat spt SIZE_FIELDS),
     * value = [min, max, default dalam px] - dipakai bareng buat validasi &
     * reset (lihat saveGlobalSettings()/resetContentSettings()), diterapkan lewat
     * inline style padding/border-width/border-radius di partials/seat-box.blade.php.
     */
    public const BOX_STYLE_FIELDS = [
        'seat_padding' => ['label' => 'Padding Kotak', 'min' => 0, 'max' => 40, 'default' => 0],
        'seat_border_width' => ['label' => 'Tebal Border', 'min' => 0, 'max' => 20, 'default' => 4],
        'seat_border_radius' => ['label' => 'Rounded Border', 'min' => 0, 'max' => 40, 'default' => 12],
        'seat_gap' => ['label' => 'Jarak Antar Kotak', 'min' => 0, 'max' => 40, 'default' => 12],
        // Negatif = naik, positif = turun - cuma menggeser icon/teks kotak KOSONG
        // (empty_icon/empty_label), bukan avatar/nama/coin kursi yang sudah terisi.
        // Dipisah jadi 2 field (bukan 1 offset gabungan) supaya icon & teks bisa
        // diatur naik-turunnya independen satu sama lain.
        'empty_icon_offset_y' => ['label' => 'Naik/Turun Icon Kotak Kosong', 'min' => -100, 'max' => 100, 'default' => 0],
        'empty_label_offset_y' => ['label' => 'Naik/Turun Teks Kotak Kosong', 'min' => -100, 'max' => 100, 'default' => 0],
        'mic_offset_y' => ['label' => 'Naik/Turun Icon Mic (Elevator)', 'min' => -100, 'max' => 100, 'default' => 0],
        'coin_offset_y' => ['label' => 'Naik/Turun Badge Coin (Elevator)', 'min' => -100, 'max' => 100, 'default' => 0],
        'name_offset_y' => ['label' => 'Naik/Turun Badge Nama (Elevator)', 'min' => -100, 'max' => 100, 'default' => 0],
        'gift_badge_offset_y' => ['label' => 'Naik/Turun Icon Gift Pemetaan (Elevator)', 'min' => -100, 'max' => 100, 'default' => 0],
        'avatar_offset_y' => ['label' => 'Naik/Turun Foto Avatar', 'min' => -100, 'max' => 100, 'default' => 0],
        // Geser kiri/kanan (X) - pendamping *_offset_y di atas. Negatif = geser kiri,
        // positif = geser kanan.
        'coin_offset_x' => ['label' => 'Geser Kiri/Kanan Badge Coin', 'min' => -100, 'max' => 100, 'default' => 0],
        'name_offset_x' => ['label' => 'Geser Kiri/Kanan Badge Nama', 'min' => -100, 'max' => 100, 'default' => 0],
        'mic_offset_x' => ['label' => 'Geser Kiri/Kanan Icon Mic', 'min' => -100, 'max' => 100, 'default' => 0],
        'gift_badge_offset_x' => ['label' => 'Geser Kiri/Kanan Icon Gift Pemetaan', 'min' => -100, 'max' => 100, 'default' => 0],
        'empty_icon_offset_x' => ['label' => 'Geser Kiri/Kanan Icon Kotak Kosong', 'min' => -100, 'max' => 100, 'default' => 0],
        'empty_label_offset_x' => ['label' => 'Geser Kiri/Kanan Teks Kotak Kosong', 'min' => -100, 'max' => 100, 'default' => 0],
        'avatar_offset_x' => ['label' => 'Geser Kiri/Kanan Foto Avatar', 'min' => -100, 'max' => 100, 'default' => 0],
        // Nama Host (App\Enums\SeatRole::Host) - teks bold tanpa badge di pojok kiri
        // bawah kotak, lihat partials/seat-box.blade.php. Terpisah dari
        // name_offset_x/y krn stylenya beda total (bukan badge pil).
        'host_name_offset_y' => ['label' => 'Naik/Turun Nama Host', 'min' => -100, 'max' => 100, 'default' => 0],
        'host_name_offset_x' => ['label' => 'Geser Kiri/Kanan Nama Host', 'min' => -100, 'max' => 100, 'default' => 0],
        // Tulisan badge "Host" (App\Enums\SeatRole::Host, pojok kiri ATAS - beda dari
        // Nama Host di atas yang di kiri BAWAH tanpa badge) - warna/ukuran badge-nya
        // TETAP per-BG doang (project_live_backgrounds.host_badge_bg_color dkk, tidak
        // ada versi global), cuma posisi yang dapat tingkat GLOBAL di sini.
        'host_badge_offset_y' => ['label' => 'Naik/Turun Tulisan Host', 'min' => -100, 'max' => 100, 'default' => 0],
        'host_badge_offset_x' => ['label' => 'Geser Kiri/Kanan Tulisan Host', 'min' => -100, 'max' => 100, 'default' => 0],
    ];

    public array $boxStyle = [];

    /**
     * Font teks Nama Host (App\Enums\SeatFont) - GLOBAL utk semua kotak yang jadi
     * Host. Default (string) = pakai bawaan (Figtree) - disimpan null di DB kalau
     * masih Default, sama pola dgn $emptyLabelFont di bawah.
     */
    public string $hostNameFont = 'default';

    /**
     * Font teks kotak kosong GLOBAL (App\Models\ProjectLive::empty_label_font) -
     * fallback kalau kotak tidak punya font LOKAL sendiri (project_live_details.
     * font, diatur lewat tombol "Custom" di Preview Live).
     */
    public string $emptyLabelFont = 'default';

    /**
     * Teks & font GLOBAL tulisan badge "Host" (App\Models\ProjectLive::
     * host_badge_text/host_badge_font) - fallback kalau kotak BG tidak punya
     * override LOKAL sendiri (project_live_backgrounds.host_badge_text/font,
     * diatur lewat toggle "Tulisan Host" di Preview Live). Teks kosong = pakai
     * literal "Host" (perilaku default lama).
     */
    public string $hostBadgeText = '';

    public string $hostBadgeFont = 'default';

    /**
     * Warna GLOBAL border kotak & BG kotak kosong - fallback kalau override LOKAL
     * kotak (tombol "Custom" di Preview Live, project_live_details.border_color/
     * empty_bg_color) tidak diaktifkan. String kosong = pakai perilaku default LAMA
     * (border-white/15 dari Tailwind, #000000 hardcode) - lihat
     * partials/seat-box.blade.php.
     */
    public string $seatBorderColor = '';

    public string $seatEmptyBgColor = '';

    /**
     * Show/hide GLOBAL per elemen (default utk semua kotak, fallback kalau
     * override LOKAL kotak - tombol "Custom" di Preview Live utk 6 elemen
     * pertama, modal "Edit Kotak BG" utk host_badge/host_name - tidak
     * diaktifkan). Key SAMA persis dgn nama kolom project_lives sebelum
     * "_visible". Lihat App\Support\SeatStyleResolver::isVisible().
     */
    public const VISIBILITY_FIELDS = ['coin', 'name', 'gift_badge', 'mic', 'empty_icon', 'empty_label', 'host_badge', 'host_name'];

    public array $visibility = [];

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
        $this->hostNameFont = $projectLive->host_name_font ?? 'default';
        $this->emptyLabelFont = $projectLive->empty_label_font ?? 'default';
        $this->hostBadgeText = (string) $projectLive->host_badge_text;
        $this->hostBadgeFont = $projectLive->host_badge_font ?? 'default';
        $this->seatBorderColor = (string) $projectLive->seat_border_color;
        $this->seatEmptyBgColor = (string) $projectLive->seat_empty_bg_color;

        foreach (self::VISIBILITY_FIELDS as $field) {
            $this->visibility[$field] = (bool) $projectLive->{$field.'_visible'};
        }

        foreach (self::SIZE_FIELDS as $field) {
            $this->sizes[$field] = $projectLive->{$field.'_size'};
        }

        foreach (self::BOX_STYLE_FIELDS as $field => $config) {
            $this->boxStyle[$field] = $projectLive->{$field};
        }
    }

    /**
     * SATU-SATUNYA tombol Simpan utk seluruh "Ukuran & Posisi Kotak Live" (dulu 4
     * method/tombol terpisah: saveSizes/saveBoxStyle/saveSeatColors/saveMicIcon,
     * lalu digabung jadi saveContentSettings, sekarang digabung LAGI dgn warna
     * kotak, font Nama Host, font teks kotak kosong, & icon kotak kosong global -
     * admin nggak perlu klik Simpan berkali-kali per bagian) - nulis $sizes,
     * $boxStyle, warna, font, & (kalau ada file baru) icon mic/kotak kosong
     * sekaligus dalam SATU request.
     */
    /**
     * Cuma ubah WORKING COPY (belum tersimpan) - baru benar2 kepakai kalau tombol
     * Simpan (saveGlobalSettings()) di-klik, sama pola dgn $sizes/$boxStyle.
     */
    public function toggleVisibilityDraft(string $field): void
    {
        $this->visibility[$field] = ! ($this->visibility[$field] ?? true);
    }

    public function saveGlobalSettings(): void
    {
        // Beda dari display_mode (superadmin only) — settingan ini boleh diatur
        // akun role "live" yang di-assign ke project ini juga, sama seperti mereka
        // boleh edit kursi & katalog gift.
        $this->authorize('viewLive', $this->projectLive);

        $rules = collect(self::SIZE_FIELDS)
            ->mapWithKeys(fn ($field) => ["sizes.{$field}" => 'required|integer|min:50|max:200'])
            ->merge(collect(self::BOX_STYLE_FIELDS)->mapWithKeys(fn ($config, $field) => ["boxStyle.{$field}" => "required|integer|min:{$config['min']}|max:{$config['max']}"]))
            ->all();

        $rules['seatBorderColor'] = ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'];
        $rules['seatEmptyBgColor'] = ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'];
        $rules['hostNameFont'] = ['required', Rule::in(array_column(SeatFont::cases(), 'value'))];
        $rules['emptyLabelFont'] = ['required', Rule::in(array_column(SeatFont::cases(), 'value'))];
        $rules['hostBadgeText'] = 'nullable|string|max:50';
        $rules['hostBadgeFont'] = ['required', Rule::in(array_column(SeatFont::cases(), 'value'))];
        $rules['micIconFile'] = 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192';
        $rules['emptyIconFile'] = 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192';

        foreach (self::VISIBILITY_FIELDS as $field) {
            $rules["visibility.{$field}"] = 'boolean';
        }

        $validated = $this->validate($rules);

        $data = $validated['boxStyle'];

        foreach (self::SIZE_FIELDS as $field) {
            $data["{$field}_size"] = $validated['sizes'][$field];
        }

        foreach (self::VISIBILITY_FIELDS as $field) {
            $data["{$field}_visible"] = $validated['visibility'][$field];
        }

        $data['seat_border_color'] = $validated['seatBorderColor'] !== '' ? $validated['seatBorderColor'] : null;
        $data['seat_empty_bg_color'] = $validated['seatEmptyBgColor'] !== '' ? $validated['seatEmptyBgColor'] : null;
        $data['host_name_font'] = $validated['hostNameFont'] !== SeatFont::Default->value ? $validated['hostNameFont'] : null;
        $data['empty_label_font'] = $validated['emptyLabelFont'] !== SeatFont::Default->value ? $validated['emptyLabelFont'] : null;
        $data['host_badge_text'] = $validated['hostBadgeText'] !== '' ? $validated['hostBadgeText'] : null;
        $data['host_badge_font'] = $validated['hostBadgeFont'] !== SeatFont::Default->value ? $validated['hostBadgeFont'] : null;

        if ($this->micIconFile) {
            $oldIcon = $this->projectLive->mic_icon;
            $data['mic_icon'] = $this->micIconFile->store('project-lives/'.$this->projectLive->id, 'public');

            if ($oldIcon) {
                Storage::disk('public')->delete($oldIcon);
            }
        }

        if ($this->emptyIconFile) {
            $oldIcon = $this->projectLive->empty_icon;
            $data['empty_icon'] = $this->emptyIconFile->store('project-lives/'.$this->projectLive->id, 'public');

            if ($oldIcon) {
                Storage::disk('public')->delete($oldIcon);
            }
        }

        $this->projectLive->update($data);
        $this->projectLive->refresh();
        $this->reset(['micIconFile', 'emptyIconFile']);

        $this->dispatch('notify', message: 'Settingan global berhasil disimpan.');
    }

    public function resetContentSettings(): void
    {
        $this->authorize('viewLive', $this->projectLive);

        foreach (self::SIZE_FIELDS as $field) {
            $this->sizes[$field] = 100;
        }

        foreach (self::BOX_STYLE_FIELDS as $field => $config) {
            $this->boxStyle[$field] = $config['default'];
        }

        $this->saveGlobalSettings();
    }

    public function removeMicIcon(): void
    {
        $this->authorize('viewLive', $this->projectLive);

        if ($this->projectLive->mic_icon) {
            Storage::disk('public')->delete($this->projectLive->mic_icon);
        }

        $this->projectLive->update(['mic_icon' => null]);
        $this->projectLive->refresh();

        $this->dispatch('notify', message: 'Icon mic dikembalikan ke bawaan.');
    }

    public function removeEmptyIconGlobal(): void
    {
        $this->authorize('viewLive', $this->projectLive);

        if ($this->projectLive->empty_icon) {
            Storage::disk('public')->delete($this->projectLive->empty_icon);
        }

        $this->projectLive->update(['empty_icon' => null]);
        $this->projectLive->refresh();

        $this->dispatch('notify', message: 'Icon kotak kosong dikembalikan ke bawaan.');
    }

    /**
     * Arah kotak KOSONG diisi gifter baru — 'asc' (default) kotak index #1 diisi
     * duluan lanjut ke bawah, 'desc' kebalikannya (kotak paling akhir duluan,
     * lanjut ke atas). Lihat App\Services\GiftLeaderboardService::recalculate().
     */
    public function updateSeatFillDirection(string $value): void
    {
        $this->authorize('viewLive', $this->projectLive);

        $this->projectLive->update([
            'seat_fill_direction' => SeatFillDirection::from($value)->value,
        ]);

        $this->projectLive->refresh();
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
