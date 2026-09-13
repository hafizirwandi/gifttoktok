<?php

namespace App\Livewire\ProjectLive;

use App\Enums\EventTriggerType;
use App\Models\OverlayAnimation;
use App\Models\ProjectLive;
use App\Models\ProjectLiveEventTrigger;
use App\Models\TikTokGift;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Event Trigger')]
class EventTrigger extends Component
{
    public ProjectLive $projectLive;

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $type = '';

    public string $commandText = '';

    public string $minCount = '';

    public string $giftSearch = '';

    /**
     * Gift-gift yang dipilih admin buat trigger ini - BISA LEBIH DARI SATU, salah
     * satunya dipilih ACAK tiap kali trigger-nya jalan (App\Services\
     * EventTriggerProcessor::handle()). Dulu properti tunggal $giftId.
     *
     * @var array<int, int>
     */
    public array $giftIds = [];

    /**
     * Animasi overlay pilihan trigger ini - opsional, boleh lebih dari satu, salah
     * satunya dipilih ACAK (App\Services\OverlayQueueService::enqueueRandom()).
     *
     * @var array<int, int>
     */
    public array $overlayAnimationIds = [];

    public bool $active = true;

    public function mount(ProjectLive $projectLive): void
    {
        $this->authorize('viewLive', $projectLive);

        $this->projectLive = $projectLive;
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'type', 'commandText', 'minCount', 'giftSearch', 'giftIds', 'overlayAnimationIds']);
        $this->active = true;
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function openEdit(int $triggerId): void
    {
        $trigger = $this->projectLive->eventTriggers()->with(['mappedGifts', 'overlayAnimations'])->findOrFail($triggerId);

        $this->editingId = $trigger->id;
        $this->type = $trigger->type->value;
        $this->commandText = (string) $trigger->command_text;
        $this->minCount = (string) ($trigger->min_count ?? '');
        $this->giftIds = $trigger->mappedGifts->pluck('id')->all();
        $this->giftSearch = '';
        $this->overlayAnimationIds = $trigger->overlayAnimations->pluck('id')->all();
        $this->active = $trigger->active;
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->reset(['showModal', 'editingId', 'type', 'commandText', 'minCount', 'giftSearch', 'giftIds', 'overlayAnimationIds', 'active']);
        $this->resetErrorBag();
    }

    public function addGift(int $giftId): void
    {
        if (! in_array($giftId, $this->giftIds, true)) {
            $this->giftIds[] = $giftId;
        }

        $this->giftSearch = '';
    }

    public function removeGift(int $giftId): void
    {
        $this->giftIds = array_values(array_diff($this->giftIds, [$giftId]));
    }

    public function toggleOverlayAnimation(int $animationId): void
    {
        if (in_array($animationId, $this->overlayAnimationIds, true)) {
            $this->overlayAnimationIds = array_values(array_diff($this->overlayAnimationIds, [$animationId]));
        } else {
            $this->overlayAnimationIds[] = $animationId;
        }
    }

    public function save(): void
    {
        $this->authorize('viewLive', $this->projectLive);

        $this->resetErrorBag();

        if ($this->type === '') {
            $this->addError('form', 'Pilih dulu jenis trigger-nya.');

            return;
        }

        $type = EventTriggerType::from($this->type);

        if ($type->needsMappedGift() && empty($this->giftIds)) {
            $this->addError('form', 'Pilih minimal 1 gift yang mau muncul lewat hasil pencarian.');

            return;
        }

        if ($type->needsCommandText() && trim($this->commandText) === '') {
            $this->addError('form', 'Isi dulu kata command-nya.');

            return;
        }

        if ($type->needsMinCount() && (! is_numeric($this->minCount) || (int) $this->minCount < 1)) {
            $this->addError('form', 'Isi jumlah minimal tap/like (angka, minimal 1).');

            return;
        }

        $data = [
            'project_live_id' => $this->projectLive->id,
            'type' => $type->value,
            'command_text' => $type->needsCommandText() ? trim($this->commandText) : null,
            'min_count' => $type->needsMinCount() ? (int) $this->minCount : null,
            'active' => $this->active,
        ];

        if ($this->editingId) {
            $trigger = $this->projectLive->eventTriggers()->findOrFail($this->editingId);
            $trigger->update($data);
        } else {
            $trigger = ProjectLiveEventTrigger::create($data);
        }

        $trigger->mappedGifts()->sync($type->needsMappedGift() ? $this->giftIds : []);
        $trigger->overlayAnimations()->sync($this->overlayAnimationIds);

        $this->closeModal();

        $this->dispatch('notify', message: 'Event trigger berhasil disimpan.');
    }

    public function toggleActive(int $triggerId): void
    {
        $this->authorize('viewLive', $this->projectLive);

        $trigger = $this->projectLive->eventTriggers()->findOrFail($triggerId);
        $trigger->update(['active' => ! $trigger->active]);

        $this->dispatch('notify', message: $trigger->active ? 'Trigger diaktifkan.' : 'Trigger dinonaktifkan.');
    }

    public function delete(int $triggerId): void
    {
        $this->authorize('viewLive', $this->projectLive);

        $this->projectLive->eventTriggers()->whereKey($triggerId)->delete();

        $this->dispatch('notify', message: 'Trigger berhasil dihapus.');
    }

    public function render()
    {
        $triggers = $this->projectLive->eventTriggers()->with(['mappedGifts', 'overlayAnimations'])->latest()->get();

        $pickedIds = $this->giftIds;

        $giftResults = $this->giftSearch !== ''
            ? TikTokGift::where('name', 'like', '%'.$this->giftSearch.'%')
                ->when(! empty($pickedIds), fn ($q) => $q->whereNotIn('id', $pickedIds))
                ->limit(8)
                ->get()
            : collect();

        $pickedGifts = ! empty($pickedIds) ? TikTokGift::whereIn('id', $pickedIds)->get()->keyBy('id') : collect();

        return view('livewire.project-live.event-trigger', [
            'triggers' => $triggers,
            'giftResults' => $giftResults,
            'pickedGifts' => $pickedGifts,
            'overlayAnimations' => OverlayAnimation::where('active', true)->orderBy('name')->get(),
        ]);
    }
}
