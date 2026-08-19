<?php

namespace App\Livewire\ProjectLive;

use App\Enums\FrameOrientation;
use App\Models\ProjectLive;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Frame Host')]
class FrameHost extends Component
{
    public ProjectLive $projectLive;

    /**
     * Warna & radius di-stage di sini dulu (baru disimpan lewat tombol "Simpan") supaya
     * preview di halaman ini langsung ikut berubah saat diketik/dipilih, tanpa nge-save
     * tiap ketikan. Orientasi & show/hide sebaliknya langsung tersimpan begitu diklik
     * (sama seperti tombol Tata Letak Halaman Live).
     */
    public string $color = '#38bdf8';

    public int $radius = 24;

    public int $borderWidth = 8;

    public function mount(ProjectLive $projectLive): void
    {
        $this->authorize('viewLive', $projectLive);

        $this->projectLive = $projectLive;
        $this->color = $projectLive->frame_color;
        $this->radius = $projectLive->frame_radius;
        $this->borderWidth = $projectLive->frame_border_width;
    }

    public function updateOrientation(string $value): void
    {
        $this->authorize('viewLive', $this->projectLive);

        $this->projectLive->update(['frame_orientation' => FrameOrientation::from($value)->value]);
        $this->projectLive->refresh();
    }

    public function toggleVisible(): void
    {
        $this->authorize('viewLive', $this->projectLive);

        $this->projectLive->update(['frame_visible' => ! $this->projectLive->frame_visible]);
        $this->projectLive->refresh();
    }

    public function saveAppearance(): void
    {
        $this->authorize('viewLive', $this->projectLive);

        $validated = $this->validate([
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'radius' => ['required', 'integer', 'min:0', 'max:200'],
            'borderWidth' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $this->projectLive->update([
            'frame_color' => $validated['color'],
            'frame_radius' => $validated['radius'],
            'frame_border_width' => $validated['borderWidth'],
        ]);

        $this->projectLive->refresh();

        $this->dispatch('notify', message: 'Tampilan frame berhasil disimpan.');
    }

    public function render()
    {
        return view('livewire.project-live.frame-host');
    }
}
