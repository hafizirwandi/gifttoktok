<?php

namespace App\Livewire\ProjectLive;

use App\Enums\DetailSource;
use App\Enums\DetailStatus;
use App\Enums\SeatFont;
use App\Enums\SeatRole;
use App\Models\HostBadgePreset;
use App\Models\ProjectLive;
use App\Models\ProjectLiveDetail;
use App\Services\DominantColorExtractor;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.preview')]
#[Title('Preview Live')]
class PreviewLive extends Component
{
    use WithFileUploads;

    public ProjectLive $projectLive;

    public ?int $editingDetailId = null;

    public $img = null;

    public string $name = '';

    public string $coin = '0';

    /**
     * Teks kotak kosong - diisi/disimpan lewat panel "Custom" (openStyleEdit()/
     * saveStyleEdit(), bagian "Teks Kotak Kosong"), BUKAN modal Edit Kursi lagi -
     * tidak punya "versi global" (selalu per-kotak), makanya tidak ikut sistem
     * toggle lokal/global spt elemen lain di STYLE_ELEMENTS.
     */
    public string $emptyLabel = '';

    /**
     * Path GAMBAR yang lagi tersimpan di kursi ini (bukan file baru yang mau di-upload,
     * lihat $emptyIconFile utk itu) - dulu ini emoji yang dipilih dari daftar tetap,
     * sekarang diganti total jadi upload per kursi lewat panel "Custom" (App\Models\
     * ProjectLiveDetail::emptyIconUrl()). String kosong = belum ada, fallback ke '+'.
     */
    public string $emptyIcon = '';

    public $emptyIconFile = null;

    public string $hotkey = '';

    public string $status = 'hide';

    public bool $micEnabled = true;

    /**
     * Pin: kursi ini DIKECUALIKAN dari Reset Leaderboard/Reset Coin DAN dari
     * pengisian otomatis auto-gift (lihat App\Services\GiftLeaderboardService) -
     * nama/foto/coin-nya dipertahankan apa pun yang terjadi ke leaderboard, sampai
     * admin unpin manual. Cocok utk kursi sponsor/tamu tetap yang tidak boleh
     * ketiban-timpa gifter baru.
     */
    public bool $isPinned = false;

    /**
     * Font teks kotak kosong (App\Enums\SeatFont) - lihat App\Models\ProjectLiveDetail::
     * font, sama pola dgn $emptyLabel di atas (diisi/disimpan lewat panel "Custom",
     * tidak punya "versi global"). borderColor string kosong = ikut GLOBAL project_lives.
     * seat_border_color (atau default border-white/15 kalau itu juga kosong) - lihat
     * toggleBorderColor().
     */
    public string $font = 'default';

    public string $borderColor = '';

    /**
     * Tebal border KOTAK INI - satu toggle dgn $borderColor (bukan toggle sendiri),
     * cuma benar2 kepakai/tersimpan kalau $borderColor !== '' (Lokal aktif) - null
     * effectively lewat $borderColor kosong, bukan lewat properti ini sendiri. Diisi
     * dari nilai GLOBAL project_lives.seat_border_width saat toggleBorderColor()
     * dinyalakan, biar admin mulai dari tampilan yang sama.
     */
    public int $borderWidth = 4;

    /**
     * BG layar penuh yang lagi aktif - sama persis dgn App\Livewire\ProjectLive\
     * LiveShow::$screenBackground, dipakai biar Preview menampilkan BG juga.
     *
     * @var array<string, mixed>|null
     */
    public ?array $screenBackground = null;

    /**
     * Dialog edit KHUSUS kotak yang jadi BG (App\Livewire\ProjectLive\Background) -
     * beda dari $editingDetailId (modal edit kursi normal) krn yang diedit di sini
     * adalah baris project_live_backgrounds-nya (role + style badge Host), BUKAN
     * project_live_details langsung - $name/$coin/$micEnabled DIPAKAI BARENG dgn
     * modal normal di atas krn co-host butuh field yang sama persis (nama/coin/mic).
     */
    public ?int $editingBgDetailId = null;

    public ?int $editingBgId = null;

    public string $bgRole = 'none';

    public string $hostBadgeBgColor = '#f59e0b';

    public string $hostBadgeTextColor = '#000000';

    public int $hostBadgeSize = 100;

    /**
     * Teks/font/posisi tulisan badge "Host" KOTAK BG INI SAJA - null (SEMUA empat
     * properti ini SELALU null/non-null BARENG-BARENG, satu bundel) = ikut default
     * GLOBAL (project_lives.host_badge_text/font/offset_x/offset_y - Admin), diisi
     * = override LOKAL. Beda dari $hostBadgeBgColor/$hostBadgeTextColor/
     * $hostBadgeSize di atas yang SELALU per-BG (tidak ada tingkat GLOBAL). Lihat
     * toggleHostBadgeCustom().
     */
    public ?string $hostBadgeText = null;

    public ?string $hostBadgeFont = null;

    public ?int $hostBadgeOffsetX = null;

    public ?int $hostBadgeOffsetY = null;

    /**
     * Tampil/Sembunyi badge "Host" & Nama Host KOTAK BG INI SAJA - string kosong =
     * ikut default GLOBAL (project_lives.host_badge_visible/host_name_visible),
     * '1'/'0' = override LOKAL eksplisit. Sama pola dgn $borderColor/$emptyBgColor
     * (string kosong = global) - lihat toggleHostBadgeVisible()/toggleHostNameVisible().
     */
    public string $hostBadgeVisible = '';

    public string $hostNameVisible = '';

    /**
     * Logo/icon custom LOKAL (kotak BG ini saja) di samping tulisan badge "Host" -
     * $hostBadgeLogo path yang LAGI TERSIMPAN (bukan file baru, lihat
     * $hostBadgeLogoFile utk itu), string kosong = belum ada logo lokal (ikut
     * GLOBAL App\Models\ProjectLive::hostBadgeLogoUrl()). $hostBadgeLogoVisible
     * sama pola dgn $hostBadgeVisible di atas (string kosong = ikut GLOBAL
     * project_lives.host_badge_logo_visible).
     */
    public string $hostBadgeLogo = '';

    public $hostBadgeLogoFile = null;

    public string $hostBadgeLogoVisible = '';

    /**
     * Elemen visual kotak yang bisa di-override LOKAL lewat tombol "Custom" per
     * kotak (beda dari modal edit kursi/BG di atas yang isinya DATA - nama/coin/dst)
     * - masing2 elemen: 'label' (tampil di UI), 'size_col'/'offset_x_col'/
     * 'offset_y_col' (nama kolom GLOBAL project_lives yang jadi fallback kalau
     * override-nya nonaktif), 'has_icon' (mic doang, bisa upload icon per-kotak).
     * Lihat App\Support\SeatStyleResolver & partials/seat-box.blade.php.
     */
    public const STYLE_ELEMENTS = [
        // Foto user di TENGAH kotak - cuma ukuran & naik/turun (TIDAK ada geser
        // kiri/kanan, beda dari elemen lain, sesuai permintaan admin: foto ini
        // sudah di-tengah secara horizontal, jarang perlu digeser kiri/kanan) -
        // lihat 'no_offset_x' di bawah, dibaca UI panel Custom & saveStyleEdit().
        'avatar' => ['label' => 'Foto User (Tengah)', 'size_col' => 'avatar_size', 'offset_x_col' => 'avatar_offset_x', 'offset_y_col' => 'avatar_offset_y', 'no_offset_x' => true],
        'coin' => ['label' => 'Badge Coin', 'size_col' => 'coin_size', 'offset_x_col' => 'coin_offset_x', 'offset_y_col' => 'coin_offset_y', 'visible_col' => 'coin_visible'],
        'name' => ['label' => 'Badge Nama', 'size_col' => 'name_size', 'offset_x_col' => 'name_offset_x', 'offset_y_col' => 'name_offset_y', 'visible_col' => 'name_visible'],
        'gift_badge' => ['label' => 'Icon Pemetaan Gift', 'size_col' => 'gift_badge_size', 'offset_x_col' => 'gift_badge_offset_x', 'offset_y_col' => 'gift_badge_offset_y', 'visible_col' => 'gift_badge_visible'],
        // 'visible' mic BUKAN lewat style_overrides spt elemen lain - dia sudah punya
        // mekanisme sendiri dari lama (project_live_details.mic_visible, $micEnabled/
        // toggleModalMic()), jadi TIDAK ada 'visible_col' di sini. Resolusi lokal-vs-
        // global-nya khusus, lihat partials/seat-box.blade.php ($micVisible).
        'mic' => ['label' => 'Icon Mic', 'size_col' => 'mic_size', 'offset_x_col' => 'mic_offset_x', 'offset_y_col' => 'mic_offset_y', 'has_icon' => true],
        'empty_icon' => ['label' => 'Icon Kotak Kosong', 'size_col' => 'empty_icon_size', 'offset_x_col' => 'empty_icon_offset_x', 'offset_y_col' => 'empty_icon_offset_y', 'visible_col' => 'empty_icon_visible'],
        'empty_label' => ['label' => 'Teks Kotak Kosong', 'size_col' => 'empty_label_size', 'offset_x_col' => 'empty_label_offset_x', 'offset_y_col' => 'empty_label_offset_y', 'visible_col' => 'empty_label_visible'],
    ];

    public ?int $editingStyleDetailId = null;

    /**
     * Working copy yang lagi di-staging (belum Simpan) - dikunci ke bentuk
     * ['enabled' => bool, 'size' => int, 'offset_x' => int, 'offset_y' => int] per
     * key STYLE_ELEMENTS ('mic' tambahan 'icon' => path|null). Dipetakan ke/dari
     * project_live_details.style_overrides (JSON) lewat openStyleEdit()/saveStyleEdit().
     *
     * @var array<string, array<string, mixed>>
     */
    public array $styleOverrides = [];

    public string $emptyBgColor = '';

    public $localMicIconFile = null;

    public function mount(ProjectLive $projectLive): void
    {
        $this->authorize('viewLive', $projectLive);

        $this->projectLive = $projectLive;
        $this->loadScreenBackground();
    }

    private function loadScreenBackground(): void
    {
        $bg = $this->projectLive->activeScreenBackground();

        $this->screenBackground = $bg ? $bg->toLiveArray() : null;
    }

    public function hideAll(): void
    {
        $this->authorize('viewLive', $this->projectLive);

        $this->projectLive->details()->update(['status' => DetailStatus::Hide->value]);
    }

    public function showAll(): void
    {
        $this->authorize('viewLive', $this->projectLive);

        $this->projectLive->details()->update(['status' => DetailStatus::Show->value]);
    }

    public function openEdit(int $detailId): void
    {
        $detail = $this->projectLive->details()->findOrFail($detailId);

        $this->editingDetailId = $detail->id;
        $this->name = (string) $detail->name;
        $this->coin = (string) $detail->gift_total_value;
        $this->hotkey = (string) $detail->hotkey;
        $this->status = $detail->status->value;
        $this->isPinned = $detail->is_pinned;
        $this->img = null;
    }

    public function closeEdit(): void
    {
        $this->reset(['editingDetailId', 'img', 'name', 'coin', 'hotkey', 'status', 'isPinned']);
    }

    /**
     * URL preview icon kotak kosong yang LAGI TERSIMPAN (bukan file baru yang belum
     * di-upload) - dipakai panel "Custom" (bagian "Icon Kotak Kosong") buat
     * nampilin thumbnail sebelum Simpan.
     */
    public function emptyIconUrl(): ?string
    {
        return $this->emptyIcon !== '' ? Storage::disk('public')->url($this->emptyIcon) : null;
    }

    /**
     * URL preview logo badge Host LOKAL yang LAGI TERSIMPAN - dipakai modal "Edit
     * Kotak BG" (bagian "Style Badge Host") buat nampilin thumbnail.
     */
    public function hostBadgeLogoUrl(): ?string
    {
        return $this->hostBadgeLogo !== '' ? Storage::disk('public')->url($this->hostBadgeLogo) : null;
    }

    /**
     * Hapus icon kotak kosong yang lagi tersimpan, balik ke fallback default ('+') -
     * langsung tereksekusi (beda dari upload baru yang nunggu tombol Simpan).
     */
    public function removeEmptyIcon(): void
    {
        $this->authorize('viewLive', $this->projectLive);

        $detail = $this->projectLive->details()->findOrFail($this->editingStyleDetailId);

        if ($detail->empty_icon) {
            Storage::disk('public')->delete($detail->empty_icon);
        }

        $detail->update(['empty_icon' => null]);

        $this->emptyIcon = '';
    }

    public function toggleStatus(int $detailId): void
    {
        $this->authorize('viewLive', $this->projectLive);

        $detail = $this->projectLive->details()->findOrFail($detailId);

        $detail->update([
            'status' => $detail->status === DetailStatus::Hide
                ? DetailStatus::Show->value
                : DetailStatus::Hide->value,
        ]);
    }

    public function toggleModalStatus(): void
    {
        $this->status = $this->status === 'show' ? 'hide' : 'show';
    }

    public function toggleModalMic(): void
    {
        $this->micEnabled = ! $this->micEnabled;
    }

    public function toggleModalPinned(): void
    {
        $this->isPinned = ! $this->isPinned;
    }

    public function save(): void
    {
        $this->authorize('viewLive', $this->projectLive);

        $detail = $this->projectLive->details()->findOrFail($this->editingDetailId);

        $validated = $this->validate([
            'name' => 'nullable|string|max:255',
            'coin' => 'required|integer|min:0',
            'hotkey' => [
                'nullable',
                'string',
                'size:1',
                Rule::unique('project_live_details', 'hotkey')
                    ->where('project_live_id', $this->projectLive->id)
                    ->ignore($detail->id),
                function ($attribute, $value, $fail) use ($detail) {
                    if (! $value) {
                        return;
                    }

                    $conflict = $this->projectLive->findHotkeyConflict($value, "seat:{$detail->id}");

                    if ($conflict) {
                        $fail("Hotkey ini sudah dipakai sebagai {$conflict} — pilih huruf/angka lain.");
                    }
                },
            ],
            'status' => 'required|in:hide,show',
            'isPinned' => 'boolean',
            // 2048 (2MB) sebelumnya kelewat kecil buat foto HP modern — upload gagal
            // divalidasi diam-diam (cuma teks error kecil yang gampang kelewat), user
            // ngira foto-nya tidak terupload sama sekali. Dinaikkan ke 8MB.
            'img' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
        ]);

        $data = [
            'name' => $validated['name'],
            'gift_total_value' => $validated['coin'],
            'hotkey' => $validated['hotkey'] !== '' ? $validated['hotkey'] : null,
            'status' => $validated['status'],
            'is_pinned' => $validated['isPinned'],
            // Edit manual selalu mengembalikan kursi ke source "manual", supaya tidak
            // langsung ketiban timpa oleh recalculation leaderboard auto-mode berikutnya -
            // KECUALI kalau lagi di-PIN: link ke project_live_gifter_id SENGAJA
            // dipertahankan (bukan dinolkan) supaya GiftLeaderboardService::recalculate()
            // masih bisa (1) mengenali gifter aslinya biar tidak "dobel" nongol lagi di
            // kursi lain kalau dia ngasih gift baru, dan (2) tetap nambahin
            // gift_total_value kursi ini seiring round_value gifter itu terus naik
            // (lihat recalculate()). BUG YANG SUDAH KEJADIAN: sebelum ini field-nya
            // SELALU dinolkan tanpa syarat cuma gara2 admin buka modal edit buat toggle
            // pin - gifter aslinya jadi "lepas ikatan" dari kursi pin ini dan otomatis
            // dianggap gifter baru begitu ngasih gift lagi, nyasar bikin kursi baru.
            'source' => DetailSource::Manual->value,
            'project_live_gifter_id' => $validated['isPinned'] ? $detail->project_live_gifter_id : null,
        ];

        if ($this->img) {
            $oldImg = $detail->img;

            $path = $this->img->store('project-live-details/'.$this->projectLive->id, 'public');
            $data['img'] = $path;
            $data['dominant_color'] = app(DominantColorExtractor::class)->extract($this->img->getRealPath());

            if ($oldImg) {
                Storage::disk('public')->delete($oldImg);
            }
        }

        $detail->update($data);

        $this->closeEdit();

        $this->dispatch('notify', message: 'Kursi berhasil disimpan.');
    }

    /**
     * Buka dialog edit KHUSUS kotak yang jadi BG (dipanggil dari preview-live.blade.php
     * SEBAGAI GANTI openEdit() kalau kotak yang diklik punya background_id terisi) -
     * yang diedit di sini role-nya (App\Enums\SeatRole) + style badge Host, plus
     * nama/coin/mic kalau role-nya co-host (field yang SAMA dgn modal kursi normal).
     */
    public function openBgEdit(int $detailId): void
    {
        $detail = $this->projectLive->details()->with('background')->findOrFail($detailId);

        if (! $detail->background) {
            return;
        }

        $bg = $detail->background;

        $this->editingBgDetailId = $detail->id;
        $this->editingBgId = $bg->id;
        $this->bgRole = $bg->role->value;
        $this->hostBadgeBgColor = $bg->host_badge_bg_color;
        $this->hostBadgeTextColor = $bg->host_badge_text_color;
        $this->hostBadgeSize = $bg->host_badge_size;
        $this->hostBadgeText = $bg->host_badge_text;
        $this->hostBadgeFont = $bg->host_badge_font;
        $this->hostBadgeOffsetX = $bg->host_badge_offset_x;
        $this->hostBadgeOffsetY = $bg->host_badge_offset_y;
        $this->hostBadgeVisible = $bg->host_badge_visible === null ? '' : ($bg->host_badge_visible ? '1' : '0');
        $this->hostNameVisible = $bg->host_name_visible === null ? '' : ($bg->host_name_visible ? '1' : '0');
        $this->hostBadgeLogo = (string) $bg->host_badge_logo;
        $this->hostBadgeLogoFile = null;
        $this->hostBadgeLogoVisible = $bg->host_badge_logo_visible === null ? '' : ($bg->host_badge_logo_visible ? '1' : '0');

        // Field co-host - SAMA PERSIS dgn openEdit() normal, dipakai bareng. $name JUGA
        // dipakai role Host (nama Host, teks bold tanpa badge - lihat
        // partials/seat-box.blade.php), makanya diisi regardless of role, bukan cuma
        // pas co_host.
        $this->name = (string) $detail->name;
        $this->coin = (string) $detail->gift_total_value;
        $this->micEnabled = $detail->mic_visible;
    }

    public function closeBgEdit(): void
    {
        $this->reset(['editingBgDetailId', 'editingBgId', 'bgRole', 'hostBadgeBgColor', 'hostBadgeTextColor', 'hostBadgeSize', 'hostBadgeText', 'hostBadgeFont', 'hostBadgeOffsetX', 'hostBadgeOffsetY', 'hostBadgeVisible', 'hostNameVisible', 'hostBadgeLogo', 'hostBadgeLogoFile', 'hostBadgeLogoVisible', 'name', 'coin', 'micEnabled']);
    }

    /**
     * Teks/font/posisi tulisan badge "Host" - satu bundel (lihat komentar properti
     * $hostBadgeText dkk) - null = ikut GLOBAL, diisi = override LOKAL. Nyalain
     * override diisi dari nilai GLOBAL yang lagi aktif biar admin mulai dari
     * tampilan yang sama sebelum di-tweak, sama pola dgn toggleBorderColor().
     */
    public function toggleHostBadgeCustom(): void
    {
        if ($this->hostBadgeOffsetX !== null) {
            $this->hostBadgeText = null;
            $this->hostBadgeFont = null;
            $this->hostBadgeOffsetX = null;
            $this->hostBadgeOffsetY = null;

            return;
        }

        $this->hostBadgeText = $this->projectLive->host_badge_text ?? 'Host';
        $this->hostBadgeFont = $this->projectLive->host_badge_font ?? SeatFont::Default->value;
        $this->hostBadgeOffsetX = $this->projectLive->host_badge_offset_x;
        $this->hostBadgeOffsetY = $this->projectLive->host_badge_offset_y;
    }

    /**
     * Isi hostBadgeBgColor/hostBadgeTextColor (& hostBadgeFont kalau presetnya
     * punya) dari katalog MASTER App\Models\HostBadgePreset - CUMA ngisi awal,
     * BUKAN nge-lock ke preset itu, admin tetap bebas ubah manual sesudahnya lewat
     * color/font picker yang sudah ada (belum benar2 tersimpan sampai tombol
     * Simpan di-klik, sama spt field lain di modal ini).
     */
    public function applyHostBadgePreset(int $presetId): void
    {
        $preset = HostBadgePreset::findOrFail($presetId);

        $this->hostBadgeBgColor = $preset->bg_color;
        $this->hostBadgeTextColor = $preset->text_color;

        if ($preset->font) {
            // Font cuma kepakai kalau bundel Lokal (teks/font/posisi) aktif - nyalain
            // dulu kalau masih ikut Global, biar font presetnya benar2 ke-apply.
            if ($this->hostBadgeOffsetX === null) {
                $this->toggleHostBadgeCustom();
            }

            $this->hostBadgeFont = $preset->font;
        }
    }

    /**
     * hostBadgeVisible/hostNameVisible SUDAH toggle secara implisit (string kosong =
     * ikut GLOBAL project_lives.host_badge_visible/host_name_visible, '1'/'0' =
     * override LOKAL) - method ini nyediain tombol Aktif/Nonaktif spt borderColor/
     * emptyBgColor. Nyalain override diisi dari nilai GLOBAL yang lagi aktif biar
     * admin mulai dari tampilan yang sama sebelum di-tweak.
     */
    public function toggleHostBadgeVisible(): void
    {
        $this->hostBadgeVisible = $this->hostBadgeVisible !== ''
            ? ''
            : ($this->projectLive->host_badge_visible ? '1' : '0');
    }

    public function toggleHostNameVisible(): void
    {
        $this->hostNameVisible = $this->hostNameVisible !== ''
            ? ''
            : ($this->projectLive->host_name_visible ? '1' : '0');
    }

    public function toggleHostBadgeLogoVisible(): void
    {
        $this->hostBadgeLogoVisible = $this->hostBadgeLogoVisible !== ''
            ? ''
            : ($this->projectLive->host_badge_logo_visible ? '1' : '0');
    }

    /**
     * Hapus logo LOKAL kotak BG ini yang lagi tersimpan, balik ikut GLOBAL
     * (App\Models\ProjectLive::hostBadgeLogoUrl()) - langsung tereksekusi (beda
     * dari upload baru yang nunggu tombol Simpan), sama pola dgn removeEmptyIcon().
     */
    public function removeHostBadgeLogoLocal(): void
    {
        $this->authorize('viewLive', $this->projectLive);

        $bg = $this->projectLive->backgrounds()->findOrFail($this->editingBgId);

        if ($bg->host_badge_logo) {
            Storage::disk('public')->delete($bg->host_badge_logo);
        }

        $bg->update(['host_badge_logo' => null]);

        $this->hostBadgeLogo = '';
    }

    public function saveBgEdit(): void
    {
        $this->authorize('viewLive', $this->projectLive);

        $validated = $this->validate([
            'bgRole' => ['required', Rule::in(array_column(SeatRole::cases(), 'value'))],
            'hostBadgeBgColor' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'hostBadgeTextColor' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'hostBadgeSize' => 'required|integer|min:50|max:200',
            'hostBadgeText' => 'nullable|string|max:50',
            'hostBadgeFont' => ['nullable', Rule::in(array_column(SeatFont::cases(), 'value'))],
            'hostBadgeOffsetX' => 'nullable|integer|min:-100|max:100',
            'hostBadgeOffsetY' => 'nullable|integer|min:-100|max:100',
            'hostBadgeVisible' => ['nullable', Rule::in(['', '0', '1'])],
            'hostNameVisible' => ['nullable', Rule::in(['', '0', '1'])],
            'hostBadgeLogoFile' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
            'hostBadgeLogoVisible' => ['nullable', Rule::in(['', '0', '1'])],
            'name' => 'nullable|string|max:255',
            'coin' => 'required|integer|min:0',
            'micEnabled' => 'boolean',
        ]);

        $bg = $this->projectLive->backgrounds()->findOrFail($this->editingBgId);

        $data = [
            'role' => $validated['bgRole'],
            'host_badge_bg_color' => $validated['hostBadgeBgColor'],
            'host_badge_text_color' => $validated['hostBadgeTextColor'],
            'host_badge_size' => $validated['hostBadgeSize'],
            'host_badge_text' => $validated['hostBadgeText'],
            'host_badge_font' => $validated['hostBadgeFont'],
            'host_badge_offset_x' => $validated['hostBadgeOffsetX'],
            'host_badge_offset_y' => $validated['hostBadgeOffsetY'],
            'host_badge_visible' => $validated['hostBadgeVisible'] !== '' ? $validated['hostBadgeVisible'] === '1' : null,
            'host_name_visible' => $validated['hostNameVisible'] !== '' ? $validated['hostNameVisible'] === '1' : null,
            'host_badge_logo_visible' => $validated['hostBadgeLogoVisible'] !== '' ? $validated['hostBadgeLogoVisible'] === '1' : null,
        ];

        if ($this->hostBadgeLogoFile) {
            $oldLogo = $bg->host_badge_logo;
            $data['host_badge_logo'] = $this->hostBadgeLogoFile->store('project-lives/'.$this->projectLive->id, 'public');

            if ($oldLogo) {
                Storage::disk('public')->delete($oldLogo);
            }
        }

        $bg->update($data);

        // name/coin/mic cuma relevan kalau role-nya co-host (name JUGA relevan kalau
        // role-nya host, lihat komentar openBgEdit()), tapi disimpan apa adanya
        // regardless - kalau nanti role-nya di-ganti balik ke Biasa, nilainya cuma
        // tidak dirender (lihat seat-box.blade.php), tidak perlu dikosongkan di sini.
        $this->projectLive->details()->whereKey($this->editingBgDetailId)->update([
            'name' => $validated['name'],
            'gift_total_value' => $validated['coin'],
            'mic_visible' => $validated['micEnabled'],
        ]);

        $this->closeBgEdit();

        $this->dispatch('notify', message: 'Kursi berhasil disimpan.');
    }

    /**
     * Buka panel "Custom" KOTAK INI SAJA (tombol baru di tiap kartu Preview Live,
     * beda dari openEdit()/openBgEdit() yang isinya DATA kursi/BG) - override lokal
     * size/offset (+font/icon kalau relevan) per elemen, lihat STYLE_ELEMENTS.
     */
    public function openStyleEdit(int $detailId): void
    {
        $detail = $this->projectLive->details()->findOrFail($detailId);

        $this->editingStyleDetailId = $detail->id;
        $this->borderColor = (string) $detail->border_color;
        $this->borderWidth = $detail->border_width ?? $this->projectLive->seat_border_width;
        $this->emptyBgColor = (string) $detail->empty_bg_color;
        $this->localMicIconFile = null;
        // Teks/font/icon kotak kosong - pindah ke sini (bagian "Teks Kotak Kosong"/
        // "Icon Kotak Kosong" di panel Custom) dari modal Edit Kursi lama, sesuai
        // permintaan admin: elemen tampilan (ukuran/posisi/isi) kotak kosong SEMUA
        // diatur di satu tempat ini.
        $this->emptyLabel = (string) $detail->empty_label;
        $this->emptyIcon = (string) $detail->empty_icon;
        $this->emptyIconFile = null;
        $this->font = $detail->font?->value ?? SeatFont::Default->value;
        // Toggle Nyala/Sembunyi icon mic - pindah ke sini (kartu "Icon Mic") dari
        // modal Edit Kursi lama, sama alasannya dgn emptyLabel/emptyIcon/font di atas.
        $this->micEnabled = $detail->mic_visible;

        $stored = $detail->style_overrides ?? [];

        foreach (self::STYLE_ELEMENTS as $key => $config) {
            $local = $stored[$key] ?? [];

            $this->styleOverrides[$key] = [
                'enabled' => (bool) ($local['enabled'] ?? false),
                'size' => (int) ($local['size'] ?? 100),
                'offset_x' => (int) ($local['offset_x'] ?? 0),
                'offset_y' => (int) ($local['offset_y'] ?? 0),
            ];

            if (! empty($config['has_icon'])) {
                $this->styleOverrides[$key]['icon'] = $local['icon'] ?? null;
            }

            if (! empty($config['visible_col'])) {
                $this->styleOverrides[$key]['visible'] = (bool) ($local['visible'] ?? true);
            }
        }
    }

    public function closeStyleEdit(): void
    {
        $this->reset(['editingStyleDetailId', 'styleOverrides', 'emptyBgColor', 'borderColor', 'borderWidth', 'localMicIconFile', 'emptyLabel', 'emptyIcon', 'emptyIconFile', 'font', 'micEnabled']);
    }

    public function toggleStyleElement(string $key): void
    {
        $this->styleOverrides[$key]['enabled'] = ! ($this->styleOverrides[$key]['enabled'] ?? false);
    }

    public function toggleStyleElementVisible(string $key): void
    {
        $this->styleOverrides[$key]['visible'] = ! ($this->styleOverrides[$key]['visible'] ?? true);
    }

    /**
     * borderColor/emptyBgColor sudah "toggle" secara implisit (string kosong = ikut
     * GLOBAL project_lives.seat_border_color/seat_empty_bg_color, diisi = override
     * LOKAL) - method ini cuma nyediain tombol Aktif/Nonaktif yang KELIHATANNYA
     * sama kayak elemen lain (STYLE_ELEMENTS) biar konsisten, bukan mekanisme baru.
     * Nyalain override diisi dari warna GLOBAL yang lagi aktif (kalau ada) supaya
     * admin mulai dari tampilan yang sama sebelum di-tweak, bukan warna acak.
     */
    public function toggleBorderColor(): void
    {
        if ($this->borderColor !== '') {
            $this->borderColor = '';

            return;
        }

        $this->borderColor = $this->projectLive->seat_border_color ?: '#ffffff';
        $this->borderWidth = $this->projectLive->seat_border_width;
    }

    public function toggleEmptyBgColor(): void
    {
        $this->emptyBgColor = $this->emptyBgColor !== ''
            ? ''
            : ($this->projectLive->seat_empty_bg_color ?: '#000000');
    }

    /**
     * Hapus icon mic LOKAL kotak ini, balik ke fallback icon mic GLOBAL - langsung
     * tereksekusi (sama pola dgn removeEmptyIcon(), beda dari upload baru yang
     * nunggu tombol Simpan panel Custom).
     */
    public function removeLocalMicIcon(): void
    {
        $this->authorize('viewLive', $this->projectLive);

        $detail = $this->projectLive->details()->findOrFail($this->editingStyleDetailId);
        $stored = $detail->style_overrides ?? [];
        $oldIcon = $stored['mic']['icon'] ?? null;

        if ($oldIcon) {
            Storage::disk('public')->delete($oldIcon);
        }

        $stored['mic']['icon'] = null;
        $detail->update(['style_overrides' => $stored]);

        $this->styleOverrides['mic']['icon'] = null;
    }

    public function saveStyleEdit(): void
    {
        $this->authorize('viewLive', $this->projectLive);

        $rules = [
            'emptyBgColor' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'borderColor' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'borderWidth' => 'required|integer|min:0|max:20',
            'localMicIconFile' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
            'emptyLabel' => 'nullable|string|max:30',
            'emptyIconFile' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
            'font' => ['required', Rule::in(array_column(SeatFont::cases(), 'value'))],
            'micEnabled' => 'boolean',
        ];

        foreach (self::STYLE_ELEMENTS as $key => $config) {
            $rules["styleOverrides.{$key}.enabled"] = 'boolean';
            $rules["styleOverrides.{$key}.size"] = 'required|integer|min:50|max:200';
            $rules["styleOverrides.{$key}.offset_x"] = 'required|integer|min:-100|max:100';
            $rules["styleOverrides.{$key}.offset_y"] = 'required|integer|min:-100|max:100';

            if (! empty($config['visible_col'])) {
                $rules["styleOverrides.{$key}.visible"] = 'boolean';
            }
        }

        $validated = $this->validate($rules);

        $detail = $this->projectLive->details()->findOrFail($this->editingStyleDetailId);

        if ($this->localMicIconFile) {
            $oldIcon = $this->styleOverrides['mic']['icon'] ?? null;

            $this->styleOverrides['mic']['icon'] = $this->localMicIconFile->store(
                'project-live-details/'.$this->projectLive->id.'/mic-icons', 'public'
            );

            if ($oldIcon) {
                Storage::disk('public')->delete($oldIcon);
            }
        }

        $emptyIconPath = $detail->empty_icon;

        if ($this->emptyIconFile) {
            $oldEmptyIcon = $detail->empty_icon;

            $emptyIconPath = $this->emptyIconFile->store('project-live-details/'.$this->projectLive->id.'/empty-icons', 'public');

            if ($oldEmptyIcon) {
                Storage::disk('public')->delete($oldEmptyIcon);
            }
        }

        $overrides = [];

        foreach (self::STYLE_ELEMENTS as $key => $config) {
            $entry = [
                'enabled' => (bool) $validated['styleOverrides'][$key]['enabled'],
                'size' => (int) $validated['styleOverrides'][$key]['size'],
                'offset_x' => (int) $validated['styleOverrides'][$key]['offset_x'],
                'offset_y' => (int) $validated['styleOverrides'][$key]['offset_y'],
            ];

            if (! empty($config['has_icon'])) {
                $entry['icon'] = $this->styleOverrides[$key]['icon'] ?? null;
            }

            if (! empty($config['visible_col'])) {
                $entry['visible'] = (bool) $validated['styleOverrides'][$key]['visible'];
            }

            $overrides[$key] = $entry;
        }

        $detail->update([
            'style_overrides' => $overrides,
            'empty_bg_color' => $validated['emptyBgColor'] !== '' ? $validated['emptyBgColor'] : null,
            'border_color' => $validated['borderColor'] !== '' ? $validated['borderColor'] : null,
            'border_width' => $validated['borderColor'] !== '' ? $validated['borderWidth'] : null,
            'empty_label' => $validated['emptyLabel'] !== '' ? $validated['emptyLabel'] : null,
            'empty_icon' => $emptyIconPath,
            'font' => $validated['font'] !== SeatFont::Default->value ? $validated['font'] : null,
            'mic_visible' => $validated['micEnabled'],
        ]);

        $this->closeStyleEdit();

        $this->dispatch('notify', message: 'Setting lokal kotak ini disimpan.');
    }

    public function render()
    {
        // Dibentuk lewat ProjectLiveDetail::toLiveArray() yang SAMA PERSIS dgn
        // App\Livewire\ProjectLive\LiveShow - lihat komentar method itu kenapa,
        // ini yang bikin preview-live.blade.php bisa pakai partials/seat-box.blade.php
        // yang sama dan otomatis menampilkan background/font/warna border PERSIS
        // spt yang bakal tampil di halaman Live sungguhan.
        $details = $this->projectLive->details()
            ->with('background')
            ->orderBy('position')
            ->get()
            ->map(fn (ProjectLiveDetail $detail) => $detail->toLiveArray());

        return view('livewire.project-live.preview-live', [
            'details' => $details,
            'hostBadgePresets' => HostBadgePreset::orderBy('sort_order')->get(),
        ]);
    }
}
