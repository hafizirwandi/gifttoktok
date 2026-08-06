<?php

namespace App\Livewire\User;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Manajemen User')]
class Index extends Component
{
    use WithPagination;

    public bool $showModal = false;

    public ?User $editing = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $role = 'live';

    public function mount(): void
    {
        abort_unless(auth()->user()->hasRole('superadmin'), 403);
    }

    public function openCreate(): void
    {
        $this->reset(['editing', 'name', 'email', 'password']);
        $this->role = 'live';
        $this->showModal = true;
    }

    public function openEdit(User $user): void
    {
        $this->editing = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->role = $user->roles->first()?->name ?? 'live';
        $this->showModal = true;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->hasRole('superadmin'), 403);

        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                $this->editing
                    ? Rule::unique('users', 'email')->ignore($this->editing->id)
                    : Rule::unique('users', 'email'),
            ],
            'password' => $this->editing ? 'nullable|string|min:8' : 'required|string|min:8',
            'role' => 'required|in:superadmin,live',
        ]);

        if ($this->editing) {
            $this->editing->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                ...($validated['password'] ? ['password' => Hash::make($validated['password'])] : []),
            ]);
            $user = $this->editing;
        } else {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);
        }

        $user->syncRoles([$validated['role']]);

        $this->showModal = false;
        $this->reset(['editing', 'name', 'email', 'password']);
    }

    public function delete(User $user): void
    {
        abort_unless(auth()->user()->hasRole('superadmin'), 403);

        if ($user->id === auth()->id()) {
            return;
        }

        $user->delete();
    }

    public function render()
    {
        return view('livewire.user.index', [
            'users' => User::with('roles')->latest()->paginate(10),
        ]);
    }
}
