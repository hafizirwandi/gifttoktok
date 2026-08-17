<?php

namespace App\Livewire\ProjectLive;

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

        $taken = TikTokGift::where('mapped_to_gift_id', $this->targetGiftId)
            ->where('id', '!=', $this->sourceGiftId)
            ->first();

        if ($taken) {
            $this->addError('form', "Gift tujuan ini sudah dipakai untuk memetakan \"{$taken->name}\".");

            return;
        }

        TikTokGift::whereKey($this->sourceGiftId)->update(['mapped_to_gift_id' => $this->targetGiftId]);

        $this->closeModal();
    }

    public function delete(int $giftId): void
    {
        $this->authorize('viewLive', $this->projectLive);

        TikTokGift::whereKey($giftId)->update(['mapped_to_gift_id' => null]);
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

        return view('livewire.project-live.gift-mapping', [
            'mappings' => $mappings,
            'sourceResults' => $sourceResults,
            'targetResults' => $targetResults,
        ]);
    }
}
