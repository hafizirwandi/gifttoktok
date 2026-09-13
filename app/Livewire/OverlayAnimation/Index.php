<?php

namespace App\Livewire\OverlayAnimation;

use App\Models\OverlayAnimation;
use App\Models\ProjectLive;
use App\Support\WebpAnimation;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Master CRUD katalog animasi overlay (WebP animasi) - GLOBAL, dipakai bareng semua
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
     * 'manual' = admin isi sendiri (durationMs dipakai apa adanya), 'auto' = dihitung
     * otomatis dari total durasi 1 loop animasi WebP-nya sendiri (App\Support\
     * WebpAnimation) begitu tombol Simpan ditekan - lihat save().
     */
    public string $durationMode = 'manual';

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
        $this->durationMode = 'manual';
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
            'file' => [$this->editingId ? 'nullable' : 'required', 'file', 'mimes:webp', 'max:10240'],
            'active' => 'boolean',
        ]);

        $data = [
            'name' => $validated['name'],
            'duration_mode' => $validated['durationMode'],
            'active' => $validated['active'],
        ];

        $oldFile = $this->editingId ? OverlayAnimation::find($this->editingId)?->file : null;

        if ($this->file) {
            $data['file'] = $this->file->store('overlay-animations', 'public');
        }

        if ($validated['durationMode'] === 'manual') {
            $data['duration_ms'] = $validated['durationMs'];
        } else {
            // Mode "Otomatis" - hitung dari file yang BARU diupload kalau ada, atau dari
            // file yang SUDAH tersimpan kalau admin cuma ganti mode tanpa upload ulang.
            $pathToInspect = isset($data['file'])
                ? Storage::disk('public')->path($data['file'])
                : ($oldFile ? Storage::disk('public')->path($oldFile) : null);

            $detected = $pathToInspect ? WebpAnimation::totalDurationMs($pathToInspect) : null;

            if ($detected === null) {
                $this->addError('file', 'File ini tidak terdeteksi sebagai WebP beranimasi (tidak ada frame animasi) - pilih mode Manual, atau upload file WebP yang benar-benar beranimasi.');

                // File yang BARU diupload (kalau ada) sudah kepalang tersimpan sebelum
                // deteksi gagal - hapus lagi biar tidak nyampah, oldFile TETAP milik
                // record lama (tidak disentuh sama sekali kalau gagal).
                if (isset($data['file'])) {
                    Storage::disk('public')->delete($data['file']);
                }

                return;
            }

            $data['duration_ms'] = $detected;
        }

        if (isset($data['file']) && $oldFile) {
            Storage::disk('public')->delete($oldFile);
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
