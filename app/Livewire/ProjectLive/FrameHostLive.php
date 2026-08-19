<?php

namespace App\Livewire\ProjectLive;

use App\Models\ProjectLive;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.frame')]
#[Title('Frame Host')]
class FrameHostLive extends Component
{
    public ProjectLive $projectLive;

    public function mount(ProjectLive $projectLive): void
    {
        $this->authorize('viewLive', $projectLive);

        $this->projectLive = $projectLive;
    }

    /**
     * Dipanggil berkala oleh wire:poll supaya OBS Browser Source yang sudah lama
     * ditambahkan tetap ikut ke-update kalau admin ubah warna/radius/orientasi/show
     * tanpa perlu di-refresh manual di OBS.
     */
    public function poll(): void
    {
        $this->projectLive->refresh();
    }

    public function render()
    {
        return view('livewire.project-live.frame-host-live');
    }
}
