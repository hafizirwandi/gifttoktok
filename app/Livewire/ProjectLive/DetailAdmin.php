<?php

namespace App\Livewire\ProjectLive;

use App\Enums\DetailSource;
use App\Enums\DetailStatus;
use App\Enums\DisplayMode;
use App\Enums\ProjectLiveStatus;
use App\Models\ProjectLive;
use App\Models\ProjectLiveDetail;
use App\Models\TikTokGift;
use App\Services\DominantColorExtractor;
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

    public ?int $editingDetailId = null;

    public $img = null;

    public string $name = '';

    public string $coin = '0';

    public string $emptyLabel = '';

    public string $emptyIcon = '';

    public string $hotkey = '';

    public string $status = 'hide';

    public string $tiktokUsername = '';

    public string $giftSearch = '';

    public ?int $editingGiftId = null;

    public string $giftDiamondCount = '';

    /**
     * Pilihan ikon (emoji) untuk kotak kursi yang masih kosong di layar Live.
     */
    public const EMPTY_ICON_CHOICES = ['+', '❤️', '⭐', '🎁', '🎤', '🔥', '👍', '❓'];

    /**
     * Elemen kotak kursi Live yang ukurannya bisa diatur admin (persen, 100 = default),
     * lihat kolom *_size di project_lives — diterapkan lewat transform:scale di
     * partials/seat-box.blade.php.
     */
    public const SIZE_FIELDS = ['coin', 'name', 'avatar', 'empty_icon', 'empty_label', 'gift_badge', 'mic'];

    public array $sizes = [];

    public function mount(ProjectLive $projectLive): void
    {
        $this->authorize('viewLive', $projectLive);

        $this->projectLive = $projectLive;
        $this->tiktokUsername = (string) $projectLive->tiktok_username;

        foreach (self::SIZE_FIELDS as $field) {
            $this->sizes[$field] = $projectLive->{$field.'_size'};
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

    public function openEdit(int $detailId): void
    {
        $detail = $this->projectLive->details()->findOrFail($detailId);

        $this->editingDetailId = $detail->id;
        $this->name = (string) $detail->name;
        $this->coin = (string) $detail->gift_total_value;
        $this->emptyLabel = (string) $detail->empty_label;
        $this->emptyIcon = (string) ($detail->empty_icon ?: '+');
        $this->hotkey = (string) $detail->hotkey;
        $this->status = $detail->status->value;
        $this->img = null;
    }

    public function closeEdit(): void
    {
        $this->reset(['editingDetailId', 'img', 'name', 'coin', 'emptyLabel', 'emptyIcon', 'hotkey', 'status']);
    }

    public function hideAll(): void
    {
        $this->authorize('viewLive', $this->projectLive);

        $this->projectLive->details()->update(['status' => DetailStatus::Hide->value]);
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

    private function filteredGiftsQuery()
    {
        return TikTokGift::query()
            ->when($this->giftSearch, fn ($q) => $q->where('name', 'like', '%'.$this->giftSearch.'%'));
    }

    private function filteredGiftIds(): array
    {
        return $this->filteredGiftsQuery()->pluck('id')->all();
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

    public function save(): void
    {
        $this->authorize('viewLive', $this->projectLive);

        $detail = $this->projectLive->details()->findOrFail($this->editingDetailId);

        $validated = $this->validate([
            'name' => 'nullable|string|max:255',
            'coin' => 'required|integer|min:0',
            'emptyLabel' => 'nullable|string|max:30',
            'emptyIcon' => ['nullable', 'string', Rule::in(self::EMPTY_ICON_CHOICES)],
            'hotkey' => [
                'nullable',
                'string',
                'size:1',
                Rule::unique('project_live_details', 'hotkey')
                    ->where('project_live_id', $this->projectLive->id)
                    ->ignore($detail->id),
                function ($attribute, $value, $fail) {
                    if (! $value) {
                        return;
                    }

                    $value = strtolower($value);

                    if ($this->projectLive->colorHotkeys()->where('hotkey', $value)->exists()
                        || $this->projectLive->default_color_hotkey === $value) {
                        $fail('Hotkey ini sudah dipakai sebagai hotkey warna (lihat halaman Hotkey Warna) — pilih huruf/angka lain.');
                    }
                },
            ],
            'status' => 'required|in:hide,show',
            // 2048 (2MB) sebelumnya kelewat kecil buat foto HP modern — upload gagal
            // divalidasi diam-diam (cuma teks error kecil yang gampang kelewat), user
            // ngira foto-nya tidak terupload sama sekali. Dinaikkan ke 8MB.
            'img' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
        ]);

        $data = [
            'name' => $validated['name'],
            'gift_total_value' => $validated['coin'],
            'empty_label' => $validated['emptyLabel'] !== '' ? $validated['emptyLabel'] : null,
            'empty_icon' => $validated['emptyIcon'] !== '' ? $validated['emptyIcon'] : null,
            'hotkey' => $validated['hotkey'] !== '' ? $validated['hotkey'] : null,
            'status' => $validated['status'],
            // Edit manual selalu mengembalikan kursi ke source "manual", supaya tidak
            // langsung ketiban timpa oleh recalculation leaderboard auto-mode berikutnya.
            'source' => DetailSource::Manual->value,
            'project_live_gifter_id' => null,
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
            'details' => $this->projectLive->details,
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
