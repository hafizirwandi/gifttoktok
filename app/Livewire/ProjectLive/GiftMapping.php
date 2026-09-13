<?php

namespace App\Livewire\ProjectLive;

use App\Models\OverlayAnimation;
use App\Models\ProjectLive;
use App\Models\TikTokGift;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Pemetaan Gift')]
class GiftMapping extends Component
{
    public ProjectLive $projectLive;

    /**
     * Katalog gift (tiktok_gifts) itu GLOBAL, dipakai bareng semua project — jadi
     * pemetaan yang diubah di sini ikut kepakai project lain juga. Halaman ini sengaja
     * dipisah dari halaman admin utama (bukan cuma per-project) supaya tidak menuh-menuhin,
     * tapi tetap dibuka dari dalam satu project (biar akun role "live" bisa akses juga).
     */
    public bool $showModal = false;

    public ?int $editingId = null;

    public string $sourceSearch = '';

    public ?int $sourceGiftId = null;

    public string $targetSearch = '';

    public ?int $targetGiftId = null;

    /**
     * Section terpisah di bawah: pemetaan gift ASLI -> animasi overlay (App\Models\
     * TikTokGift::overlayAnimations(), dipakai App\Services\TikTokGiftEventProcessor
     * cuma utk gift asli, BUKAN sintesis Event Trigger) - GLOBAL sama seperti mapping
     * ikon di atas.
     */
    public bool $showOverlayModal = false;

    public string $overlayGiftSearch = '';

    public ?int $overlayGiftId = null;

    /**
     * @var array<int, int>
     */
    public array $overlayAnimationIds = [];

    public function mount(ProjectLive $projectLive): void
    {
        $this->authorize('viewLive', $projectLive);

        $this->projectLive = $projectLive;
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'sourceSearch', 'sourceGiftId', 'targetSearch', 'targetGiftId']);
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function openEdit(int $giftId): void
    {
        $this->authorize('viewLive', $this->projectLive);

        $gift = TikTokGift::with('mappedTo')->findOrFail($giftId);

        $this->editingId = $gift->id;
        $this->sourceGiftId = $gift->id;
        $this->sourceSearch = $gift->name;
        $this->targetGiftId = $gift->mapped_to_gift_id;
        $this->targetSearch = $gift->mappedTo->name ?? '';
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->reset(['showModal', 'editingId', 'sourceSearch', 'sourceGiftId', 'targetSearch', 'targetGiftId']);
        $this->resetErrorBag();
    }

    public function pickSource(int $giftId): void
    {
        $gift = TikTokGift::findOrFail($giftId);
        $this->sourceGiftId = $gift->id;
        $this->sourceSearch = $gift->name;
    }

    public function pickTarget(int $giftId): void
    {
        $gift = TikTokGift::findOrFail($giftId);
        $this->targetGiftId = $gift->id;
        $this->targetSearch = $gift->name;
    }

    public function clearSourcePick(): void
    {
        $this->sourceGiftId = null;
        $this->sourceSearch = '';
    }

    public function clearTargetPick(): void
    {
        $this->targetGiftId = null;
        $this->targetSearch = '';
    }

    public function save(): void
    {
        $this->authorize('viewLive', $this->projectLive);

        $this->resetErrorBag();

        if (! $this->sourceGiftId || ! $this->targetGiftId) {
            $this->addError('form', 'Pilih dulu kedua gift-nya lewat hasil pencarian.');

            return;
        }

        if ($this->sourceGiftId === $this->targetGiftId) {
            $this->addError('form', 'Gift sumber dan gift tujuan tidak boleh sama.');

            return;
        }

        // Satu gift tujuan sekarang BOLEH dipakai berkali-kali oleh gift sumber yang
        // berbeda-beda (tidak unik lagi) — biarkan saja kalau sudah dipetakan yang lain.
        TikTokGift::whereKey($this->sourceGiftId)->update(['mapped_to_gift_id' => $this->targetGiftId]);

        $this->closeModal();

        $this->dispatch('notify', message: 'Pemetaan gift berhasil disimpan.');
    }

    public function delete(int $giftId): void
    {
        $this->authorize('viewLive', $this->projectLive);

        TikTokGift::whereKey($giftId)->update(['mapped_to_gift_id' => null]);

        $this->dispatch('notify', message: 'Pemetaan gift berhasil dihapus.');
    }

    public function openOverlayCreate(): void
    {
        $this->reset(['overlayGiftSearch', 'overlayGiftId', 'overlayAnimationIds']);
        $this->resetErrorBag();
        $this->showOverlayModal = true;
    }

    public function openOverlayEdit(int $giftId): void
    {
        $gift = TikTokGift::with('overlayAnimations')->findOrFail($giftId);

        $this->overlayGiftId = $gift->id;
        $this->overlayGiftSearch = $gift->name;
        $this->overlayAnimationIds = $gift->overlayAnimations->pluck('id')->all();
        $this->resetErrorBag();
        $this->showOverlayModal = true;
    }

    public function closeOverlayModal(): void
    {
        $this->reset(['showOverlayModal', 'overlayGiftSearch', 'overlayGiftId', 'overlayAnimationIds']);
        $this->resetErrorBag();
    }

    public function pickOverlayGift(int $giftId): void
    {
        $gift = TikTokGift::findOrFail($giftId);
        $this->overlayGiftId = $gift->id;
        $this->overlayGiftSearch = $gift->name;
    }

    public function clearOverlayGiftPick(): void
    {
        $this->overlayGiftId = null;
        $this->overlayGiftSearch = '';
        $this->overlayAnimationIds = [];
    }

    public function toggleOverlayAnimation(int $animationId): void
    {
        if (in_array($animationId, $this->overlayAnimationIds, true)) {
            $this->overlayAnimationIds = array_values(array_diff($this->overlayAnimationIds, [$animationId]));
        } else {
            $this->overlayAnimationIds[] = $animationId;
        }
    }

    public function saveOverlay(): void
    {
        $this->authorize('viewLive', $this->projectLive);

        $this->resetErrorBag();

        if (! $this->overlayGiftId) {
            $this->addError('overlayForm', 'Pilih dulu gift-nya lewat hasil pencarian.');

            return;
        }

        TikTokGift::findOrFail($this->overlayGiftId)->overlayAnimations()->sync($this->overlayAnimationIds);

        $this->closeOverlayModal();

        $this->dispatch('notify', message: 'Animasi overlay gift berhasil disimpan.');
    }

    public function removeOverlayMapping(int $giftId): void
    {
        $this->authorize('viewLive', $this->projectLive);

        TikTokGift::findOrFail($giftId)->overlayAnimations()->sync([]);

        $this->dispatch('notify', message: 'Animasi overlay gift berhasil dihapus.');
    }

    public function render()
    {
        $mappings = TikTokGift::whereNotNull('mapped_to_gift_id')
            ->with('mappedTo')
            ->orderBy('name')
            ->get();

        $sourceSelectedName = $this->sourceGiftId ? TikTokGift::find($this->sourceGiftId)?->name : null;
        $targetSelectedName = $this->targetGiftId ? TikTokGift::find($this->targetGiftId)?->name : null;

        $sourceResults = $this->sourceSearch !== '' && $this->sourceSearch !== $sourceSelectedName
            ? TikTokGift::where('name', 'like', '%'.$this->sourceSearch.'%')->limit(8)->get()
            : collect();

        $targetResults = $this->targetSearch !== '' && $this->targetSearch !== $targetSelectedName
            ? TikTokGift::where('name', 'like', '%'.$this->targetSearch.'%')->limit(8)->get()
            : collect();

        $overlayGiftSelectedName = $this->overlayGiftId ? TikTokGift::find($this->overlayGiftId)?->name : null;

        $overlayGiftResults = $this->overlayGiftSearch !== '' && $this->overlayGiftSearch !== $overlayGiftSelectedName
            ? TikTokGift::where('name', 'like', '%'.$this->overlayGiftSearch.'%')->limit(8)->get()
            : collect();

        $overlayMappings = TikTokGift::has('overlayAnimations')
            ->with('overlayAnimations')
            ->orderBy('name')
            ->get();

        return view('livewire.project-live.gift-mapping', [
            'mappings' => $mappings,
            'overlayGiftResults' => $overlayGiftResults,
            'overlayMappings' => $overlayMappings,
            'overlayAnimations' => OverlayAnimation::where('active', true)->orderBy('name')->get(),
            'sourceResults' => $sourceResults,
            'targetResults' => $targetResults,
        ]);
    }
}
