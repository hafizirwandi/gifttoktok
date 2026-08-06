<?php

namespace App\Livewire\ProjectLive;

use App\Enums\ProjectLiveStatus;
use App\Models\ProjectLive;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Project Live')]
class Index extends Component
{
    use WithPagination;

    public bool $showModal = false;

    public ?ProjectLive $editing = null;

    public string $name = '';

    public string $status = 'off';

    public string $nama_akun = '';

    public string $desc = '';

    public ?int $user_id = null;

    public function mount(): void
    {
        $this->authorize('manage', ProjectLive::class);
    }

    public function liveUsers(): Collection
    {
        return User::whereHas('roles', fn ($query) => $query->where('name', 'live'))
            ->orderBy('name')
            ->get();
    }

    public function openCreate(): void
    {
        $this->reset(['editing', 'name', 'status', 'nama_akun', 'desc', 'user_id']);
        $this->status = ProjectLiveStatus::Off->value;
        $this->showModal = true;
    }

    public function openEdit(ProjectLive $projectLive): void
    {
        $this->editing = $projectLive;
        $this->name = $projectLive->name;
        $this->status = $projectLive->status->value;
        $this->nama_akun = (string) $projectLive->nama_akun;
        $this->desc = (string) $projectLive->desc;
        $this->user_id = $projectLive->user_id;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->authorize('manage', ProjectLive::class);

        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|in:live,off',
            'nama_akun' => 'nullable|string|max:255',
            'desc' => 'nullable|string|max:2000',
            'user_id' => [
                'nullable',
                'exists:users,id',
                $this->editing
                    ? Rule::unique('project_lives', 'user_id')->ignore($this->editing->id)
                    : Rule::unique('project_lives', 'user_id'),
            ],
        ]);

        if ($this->editing) {
            $this->editing->update($validated);
        } else {
            ProjectLive::create($validated);
        }

        $this->showModal = false;
        $this->reset(['editing', 'name', 'status', 'nama_akun', 'desc', 'user_id']);
    }

    public function delete(ProjectLive $projectLive): void
    {
        $this->authorize('manage', ProjectLive::class);

        $projectLive->delete();
    }

    public function render()
    {
        return view('livewire.project-live.index', [
            'projects' => ProjectLive::with('user')->latest()->paginate(10),
            'liveUsers' => $this->liveUsers(),
        ]);
    }
}
