<?php

namespace App\Livewire\OverlayAnimation;

use App\Models\OverlayAnimation;
use App\Models\ProjectLive;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Master CRUD katalog animasi overlay (video WebM) - GLOBAL, dipakai bareng semua
 * project (sama pola dgn katalog tiktok_gifts) lewat App\Models\TikTokGift::
 * overlayAnimations() atau App\Models\ProjectLiveEventTrigger::overlayAnimations().
 * Superadmin-only, sama seperti halaman Users/Pengirim Gift.
 */
#[Layout('layouts.app')]
#[Title('Animasi Overlay')]
class Index extends Component
{
    use WithFileUploads;

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    /**
     * 'auto' = App\Livewire\ProjectLive\OverlayShow nunggu event "ended" bawaan
     * <video> (diputar sampai video-nya sendiri benar2 selesai, TANPA perlu tahu
     * durasi sama sekali - beda dari WebP dulu yang tidak punya event ini). 'manual'
     * = admin maksa potong di detik tertentu (durationMs) biarpun videonya lebih
     * panjang/looping.
     */
    public string $durationMode = 'auto';

    public string $durationMs = '3000';

    public bool $active = true;

    public $file = null;

    public function mount(): void
    {
        $this->authorize('manage', ProjectLive::class);
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'name', 'file']);
        $this->durationMode = 'auto';
        $this->durationMs = '3000';
        $this->active = true;
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $animation = OverlayAnimation::findOrFail($id);

        $this->editingId = $animation->id;
        $this->name = $animation->name;
        $this->durationMode = $animation->duration_mode;
        $this->durationMs = (string) $animation->duration_ms;
        $this->active = $animation->active;
        $this->file = null;
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->reset(['showModal', 'editingId', 'name', 'durationMode', 'durationMs', 'active', 'file']);
        $this->resetErrorBag();
    }

    public function save(): void
    {
        $this->authorize('manage', ProjectLive::class);

        $validated = $this->validate([
            'name' => 'required|string|max:100',
            'durationMode' => ['required', Rule::in(['manual', 'auto'])],
            'durationMs' => $this->durationMode === 'manual' ? 'required|integer|min:200|max:60000' : 'nullable',
            'file' => [$this->editingId ? 'nullable' : 'required', 'file', 'mimes:webm', 'max:51200'],
            'active' => 'boolean',
        ]);

        $data = [
            'name' => $validated['name'],
            'duration_mode' => $validated['durationMode'],
            // Cuma benar2 dipakai kalau mode-nya "manual" (lihat overlay-show.blade.php)
            // - tetap disimpan apa adanya di mode "auto" (nilai lama/default), tidak
            // ngefek krn Show pakai event "ended" video, bukan timer.
            'duration_ms' => $validated['durationMode'] === 'manual' ? $validated['durationMs'] : 0,
            'active' => $validated['active'],
        ];

        if ($this->file) {
            $oldFile = $this->editingId ? OverlayAnimation::find($this->editingId)?->file : null;

            $data['file'] = $this->file->store('overlay-animations', 'public');

            if ($oldFile) {
                Storage::disk('public')->delete($oldFile);
            }
        }

        if ($this->editingId) {
            OverlayAnimation::whereKey($this->editingId)->update($data);
        } else {
            OverlayAnimation::create($data);
        }

        $this->closeModal();

        $this->dispatch('notify', message: 'Animasi overlay berhasil disimpan.');
    }

    public function toggleActive(int $id): void
    {
        $this->authorize('manage', ProjectLive::class);

        $animation = OverlayAnimation::findOrFail($id);
        $animation->update(['active' => ! $animation->active]);
    }

    public function delete(int $id): void
    {
        $this->authorize('manage', ProjectLive::class);

        $animation = OverlayAnimation::findOrFail($id);

        if ($animation->file) {
            Storage::disk('public')->delete($animation->file);
        }

        $animation->delete();

        $this->dispatch('notify', message: 'Animasi overlay dihapus.');
    }

    public function render()
    {
        return view('livewire.overlay-animation.index', [
            'animations' => OverlayAnimation::latest()->get(),
        ]);
    }
}
