<?php

namespace App\Livewire\ProjectLive;

use App\Enums\DetailStatus;
use App\Enums\ProjectLiveStatus;
use App\Models\ProjectLive;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.live')]
#[Title('Live')]
class LiveShow extends Component
{
    public ProjectLive $projectLive;

    public Collection $details;

    public function mount(ProjectLive $projectLive): void
    {
        $this->authorize('viewLive', $projectLive);

        $this->projectLive = $projectLive;
        $this->syncFromDatabase();
    }

    public function toggleByHotkey(int $detailId): void
    {
        if (! $this->projectLive->isLive()) {
            return;
        }

        $detail = $this->projectLive->details()->find($detailId);

        if (! $detail) {
            return;
        }

        if ($detail->status === DetailStatus::Hide) {
            $detail->update(['status' => DetailStatus::Show]);
        }

        // Hotkey ditekan lagi saat status show -> tidak perlu update apa pun,
        // syncFromDatabase() di bawah sudah menarik data terbaru (name/img/follower).

        $this->syncFromDatabase();
    }

    public function syncFromDatabase(): void
    {
        $this->projectLive->refresh();
        $this->details = $this->projectLive->details()->orderBy('position')->get();
    }

    public function render()
    {
        return view('livewire.project-live.live-show');
    }
}
