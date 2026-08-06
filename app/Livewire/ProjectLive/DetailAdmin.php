<?php

namespace App\Livewire\ProjectLive;

use App\Enums\DetailStatus;
use App\Models\ProjectLive;
use App\Models\ProjectLiveDetail;
use App\Services\DominantColorExtractor;
use Illuminate\Support\Facades\Storage;
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

    public string $hotkey = '';

    public string $status = 'hide';

    public function mount(ProjectLive $projectLive): void
    {
        $this->authorize('manage', ProjectLive::class);

        $this->projectLive = $projectLive;
    }

    public function openEdit(int $detailId): void
    {
        $detail = $this->projectLive->details()->findOrFail($detailId);

        $this->editingDetailId = $detail->id;
        $this->name = (string) $detail->name;
        $this->follower = (string) $detail->follower;
        $this->hotkey = (string) $detail->hotkey;
        $this->status = $detail->status->value;
        $this->img = null;
    }

    public function closeEdit(): void
    {
        $this->reset(['editingDetailId', 'img', 'name', 'follower', 'hotkey', 'status']);
    }

    public function save(): void
    {
        $this->authorize('manage', ProjectLive::class);

        $detail = $this->projectLive->details()->findOrFail($this->editingDetailId);

        $validated = $this->validate([
            'name' => 'nullable|string|max:255',
            'follower' => 'nullable|string|max:50',
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
            'hotkey' => $validated['hotkey'] !== '' ? $validated['hotkey'] : null,
            'status' => $validated['status'],
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
        ]);
    }
}
