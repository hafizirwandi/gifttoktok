<?php

namespace App\Livewire\ProjectLive;

use App\Enums\EventTriggerType;
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

    public ?int $giftId = null;

    public bool $active = true;

    public function mount(ProjectLive $projectLive): void
    {
        $this->authorize('viewLive', $projectLive);

        $this->projectLive = $projectLive;
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'type', 'commandText', 'minCount', 'giftSearch', 'giftId']);
        $this->active = true;
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function openEdit(int $triggerId): void
    {
        $trigger = $this->projectLive->eventTriggers()->with('mappedGift')->findOrFail($triggerId);

        $this->editingId = $trigger->id;
        $this->type = $trigger->type->value;
        $this->commandText = (string) $trigger->command_text;
        $this->minCount = (string) ($trigger->min_count ?? '');
        $this->giftId = $trigger->mapped_gift_id;
        $this->giftSearch = $trigger->mappedGift->name ?? '';
        $this->active = $trigger->active;
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->reset(['showModal', 'editingId', 'type', 'commandText', 'minCount', 'giftSearch', 'giftId', 'active']);
        $this->resetErrorBag();
    }

    public function pickGift(int $giftId): void
    {
        $gift = TikTokGift::findOrFail($giftId);
        $this->giftId = $gift->id;
        $this->giftSearch = $gift->name;
    }

    public function clearGiftPick(): void
    {
        $this->giftId = null;
        $this->giftSearch = '';
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

        if ($type->needsMappedGift() && ! $this->giftId) {
            $this->addError('form', 'Pilih dulu gift yang mau muncul lewat hasil pencarian.');

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
            'mapped_gift_id' => $type->needsMappedGift() ? $this->giftId : null,
            'command_text' => $type->needsCommandText() ? trim($this->commandText) : null,
            'min_count' => $type->needsMinCount() ? (int) $this->minCount : null,
            'active' => $this->active,
        ];

        if ($this->editingId) {
            $this->projectLive->eventTriggers()->whereKey($this->editingId)->update($data);
        } else {
            ProjectLiveEventTrigger::create($data);
        }

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
        $triggers = $this->projectLive->eventTriggers()->with('mappedGift')->latest()->get();

        $selectedName = $this->giftId ? TikTokGift::find($this->giftId)?->name : null;

        $giftResults = $this->giftSearch !== '' && $this->giftSearch !== $selectedName
            ? TikTokGift::where('name', 'like', '%'.$this->giftSearch.'%')->limit(8)->get()
            : collect();

        return view('livewire.project-live.event-trigger', [
            'triggers' => $triggers,
            'giftResults' => $giftResults,
        ]);
    }
}
