<?php

namespace App\Livewire\ProjectLive;

use App\Enums\DetailSource;
use App\Enums\DetailStatus;
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

    public string $follower = '';

    public string $coin = '0';

    public string $hotkey = '';

    public string $status = 'hide';

    public string $tiktokUsername = '';

    public string $giftSearch = '';

    public ?int $editingGiftId = null;

    public string $giftDiamondCount = '';

    public function mount(ProjectLive $projectLive): void
    {
        $this->authorize('viewLive', $projectLive);

        $this->projectLive = $projectLive;
        $this->tiktokUsername = (string) $projectLive->tiktok_username;
    }

    public function openEdit(int $detailId): void
    {
        $detail = $this->projectLive->details()->findOrFail($detailId);

        $this->editingDetailId = $detail->id;
        $this->name = (string) $detail->name;
        $this->follower = (string) $detail->follower;
        $this->coin = (string) $detail->gift_total_value;
        $this->hotkey = (string) $detail->hotkey;
        $this->status = $detail->status->value;
        $this->img = null;
    }

    public function closeEdit(): void
    {
        $this->reset(['editingDetailId', 'img', 'name', 'follower', 'coin', 'hotkey', 'status']);
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
     * bersama oleh semua project — sengaja dibatasi hanya untuk superadmin, beda dengan
     * toggleGiftRule() yang cuma mengubah aturan on/off per project.
     */
    public function openEditGiftDiamond(int $giftId): void
    {
        $this->authorize('manage', ProjectLive::class);

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
        $this->authorize('manage', ProjectLive::class);

        $validated = $this->validate([
            'giftDiamondCount' => 'required|integer|min:0',
        ]);

        TikTokGift::whereKey($this->editingGiftId)->update([
            'diamond_count' => $validated['giftDiamondCount'],
        ]);

        $this->reset(['editingGiftId', 'giftDiamondCount']);
    }

    public function deleteGift(int $giftId): void
    {
        $this->authorize('manage', ProjectLive::class);

        TikTokGift::whereKey($giftId)->delete();
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
            'follower' => 'nullable|string|max:50',
            'coin' => 'required|integer|min:0',
            'hotkey' => [
                'nullable',
                'string',
                'size:1',
                Rule::unique('project_live_details', 'hotkey')
                    ->where('project_live_id', $this->projectLive->id)
                    ->ignore($detail->id),
            ],
            'status' => 'required|in:hide,show',
            'img' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $data = [
            'name' => $validated['name'],
            'follower' => $validated['follower'],
            'gift_total_value' => $validated['coin'],
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
    }

    public function render()
    {
        return view('livewire.project-live.detail-admin', [
            'details' => $this->projectLive->details,
            'gifts' => $this->filteredGiftsQuery()->orderByDesc('diamond_count')->get(),
            'enabledGiftIds' => $this->projectLive->enabledGifts()->pluck('tiktok_gifts.id')->all(),
            'giftCatalogCount' => TikTokGift::count(),
            // ::max() adalah agregat mentah (bukan hasil hydrate model), jadi TIDAK melalui
            // cast atribut model — hasilnya string, bukan Carbon, harus di-parse manual.
            'giftCatalogUpdatedAt' => ($max = TikTokGift::max('updated_at')) ? \Carbon\Carbon::parse($max) : null,
        ]);
    }
}
