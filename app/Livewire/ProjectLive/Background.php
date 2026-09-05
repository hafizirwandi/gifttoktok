<?php

namespace App\Livewire\ProjectLive;

use App\Enums\BackgroundFit;
use App\Enums\BackgroundPlacement;
use App\Enums\BackgroundType;
use App\Models\ProjectLive;
use App\Models\ProjectLiveBackground;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Background')]
class Background extends Component
{
    use WithFileUploads;

    public ProjectLive $projectLive;

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $type = 'image';

    public string $placement = 'screen';

    public ?int $seatPosition = null;

    public $file = null;

    public string $fitMode = 'cover';

    public int $offsetX = 0;

    public int $offsetY = 0;

    public int $scale = 100;

    public function mount(ProjectLive $projectLive): void
    {
        $this->authorize('viewLive', $projectLive);

        $this->projectLive = $projectLive;
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'name', 'file', 'offsetX', 'offsetY']);
        $this->type = BackgroundType::Image->value;
        $this->placement = BackgroundPlacement::Screen->value;
        $this->seatPosition = null;
        $this->fitMode = BackgroundFit::Cover->value;
        $this->scale = 100;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $bg = $this->projectLive->backgrounds()->findOrFail($id);

        $this->editingId = $bg->id;
        $this->name = (string) $bg->name;
        $this->type = $bg->type->value;
        $this->placement = $bg->placement->value;
        $this->seatPosition = $bg->seat_position;
        $this->fitMode = $bg->fit_mode->value;
        $this->offsetX = $bg->offset_x;
        $this->offsetY = $bg->offset_y;
        $this->scale = $bg->scale;
        $this->file = null;
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->reset([
            'editingId', 'name', 'file', 'seatPosition', 'offsetX', 'offsetY',
        ]);
        $this->type = BackgroundType::Image->value;
        $this->placement = BackgroundPlacement::Screen->value;
        $this->fitMode = BackgroundFit::Cover->value;
        $this->scale = 100;
    }

    public function save(): void
    {
        $seatCount = $this->projectLive->display_mode->seatCount();

        $validated = $this->validate([
            'name' => 'nullable|string|max:255',
            'type' => 'required|in:image,video',
            'placement' => 'required|in:screen,seat',
            'seatPosition' => 'required_if:placement,seat|nullable|integer|min:1|max:'.$seatCount,
            'fitMode' => 'required|in:cover,contain,stretch',
            'offsetX' => 'integer',
            'offsetY' => 'integer',
            'scale' => 'integer|min:10|max:300',
            // Rule array TIDAK dipecah otomatis di tanda "|" per elemen (beda dari rule
            // string biasa) - tiap rule harus jadi elemen array terpisah sendiri-sendiri,
            // makanya di-spread pakai [...] bukan digabung jadi 1 string panjang.
            'file' => [
                $this->editingId ? 'nullable' : 'required',
                ...($this->type === 'video'
                    ? ['mimes:mp4,webm', 'max:51200']
                    : ['image', 'mimes:jpg,jpeg,png,webp', 'max:8192']),
            ],
        ]);

        $data = [
            'project_live_id' => $this->projectLive->id,
            'name' => $validated['name'] !== '' ? $validated['name'] : null,
            'type' => $validated['type'],
            'placement' => $validated['placement'],
            'seat_position' => $validated['placement'] === 'seat' ? $validated['seatPosition'] : null,
            'fit_mode' => $validated['fitMode'],
            'offset_x' => $validated['offsetX'],
            'offset_y' => $validated['offsetY'],
            'scale' => $validated['scale'],
        ];

        $bg = $this->editingId
            ? $this->projectLive->backgrounds()->findOrFail($this->editingId)
            : new ProjectLiveBackground;

        if ($this->file) {
            $oldFile = $bg->file;

            $data['file'] = $this->file->store('project-live-backgrounds/'.$this->projectLive->id, 'public');

            if ($oldFile) {
                Storage::disk('public')->delete($oldFile);
            }
        }

        $bg->fill($data);
        $bg->save();

        $this->closeModal();
        $this->dispatch('notify', message: 'Background berhasil disimpan.');
    }

    /**
     * Aktifkan satu BG - kalau placement=screen, nonaktifkan BG screen lain (cuma 1
     * boleh aktif sekaligus). Kalau placement=seat, nonaktifkan BG lain di POSISI
     * yang sama, lalu tandai kursi itu jadi slot BG (background_id) dan bersihkan
     * data gifter lama yang mungkin masih nyangkut di situ (pola sama persis dgn
     * App\Services\GiftLeaderboardService::emptySeat()) - kursi ini otomatis
     * dikecualikan dari auto-gift selama background_id terisi (lihat
     * GiftLeaderboardService::recalculate()).
     */
    public function activate(int $id): void
    {
        DB::transaction(function () use ($id) {
            $bg = $this->projectLive->backgrounds()->lockForUpdate()->findOrFail($id);

            if ($bg->placement === BackgroundPlacement::Screen) {
                $this->projectLive->backgrounds()
                    ->where('placement', BackgroundPlacement::Screen->value)
                    ->where('id', '!=', $bg->id)
                    ->update(['is_active' => false]);
            } else {
                $this->projectLive->backgrounds()
                    ->where('placement', BackgroundPlacement::Seat->value)
                    ->where('seat_position', $bg->seat_position)
                    ->where('id', '!=', $bg->id)
                    ->update(['is_active' => false]);

                $this->projectLive->details()->where('position', $bg->seat_position)->update([
                    'background_id' => $bg->id,
                    'name' => null,
                    'img' => null,
                    'gift_total_value' => 0,
                    'project_live_gifter_id' => null,
                    'dominant_color' => '#111111',
                ]);
            }

            $bg->update(['is_active' => true]);
        });

        $this->dispatch('notify', message: 'Background diaktifkan.');
    }

    public function deactivate(int $id): void
    {
        DB::transaction(function () use ($id) {
            $bg = $this->projectLive->backgrounds()->lockForUpdate()->findOrFail($id);

            $bg->update(['is_active' => false]);

            if ($bg->placement === BackgroundPlacement::Seat) {
                $this->projectLive->details()
                    ->where('position', $bg->seat_position)
                    ->where('background_id', $bg->id)
                    ->update(['background_id' => null]);
            }
        });

        $this->dispatch('notify', message: 'Background dinonaktifkan.');
    }

    public function delete(int $id): void
    {
        $bg = $this->projectLive->backgrounds()->findOrFail($id);

        if ($bg->is_active) {
            $this->deactivate($id);
            $bg->refresh();
        }

        if ($bg->file) {
            Storage::disk('public')->delete($bg->file);
        }

        $bg->delete();

        $this->dispatch('notify', message: 'Background dihapus.');
    }

    public function render()
    {
        return view('livewire.project-live.background', [
            'backgrounds' => $this->projectLive->backgrounds()->orderByDesc('created_at')->get(),
            'seatCount' => $this->projectLive->display_mode->seatCount(),
        ]);
    }
}
