<?php

namespace App\Livewire\ProjectLive;

use App\Enums\DetailSource;
use App\Enums\DetailStatus;
use App\Models\ProjectLive;
use App\Services\DominantColorExtractor;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Preview Live')]
class PreviewLive extends Component
{
    use WithFileUploads;

    public ProjectLive $projectLive;

    public ?int $editingDetailId = null;

    public $img = null;

    public string $name = '';

    public string $coin = '0';

    public string $emptyLabel = '';

    public string $emptyIcon = '';

    public string $hotkey = '';

    public string $status = 'hide';

    /**
     * Pilihan ikon (emoji) untuk kotak kursi yang masih kosong di layar Live.
     */
    public const EMPTY_ICON_CHOICES = ['+', '❤️', '⭐', '🎁', '🎤', '🔥', '👍', '❓'];

    public function mount(ProjectLive $projectLive): void
    {
        $this->authorize('viewLive', $projectLive);

        $this->projectLive = $projectLive;
    }

    public function hideAll(): void
    {
        $this->authorize('viewLive', $this->projectLive);

        $this->projectLive->details()->update(['status' => DetailStatus::Hide->value]);
    }

    public function openEdit(int $detailId): void
    {
        $detail = $this->projectLive->details()->findOrFail($detailId);

        $this->editingDetailId = $detail->id;
        $this->name = (string) $detail->name;
        $this->coin = (string) $detail->gift_total_value;
        $this->emptyLabel = (string) $detail->empty_label;
        $this->emptyIcon = (string) ($detail->empty_icon ?: '+');
        $this->hotkey = (string) $detail->hotkey;
        $this->status = $detail->status->value;
        $this->img = null;
    }

    public function closeEdit(): void
    {
        $this->reset(['editingDetailId', 'img', 'name', 'coin', 'emptyLabel', 'emptyIcon', 'hotkey', 'status']);
    }

    public function toggleStatus(int $detailId): void
    {
        $this->authorize('viewLive', $this->projectLive);

        $detail = $this->projectLive->details()->findOrFail($detailId);

        $detail->update([
            'status' => $detail->status === DetailStatus::Hide
                ? DetailStatus::Show->value
                : DetailStatus::Hide->value,
        ]);
    }

    public function toggleModalStatus(): void
    {
        $this->status = $this->status === 'show' ? 'hide' : 'show';
    }

    public function save(): void
    {
        $this->authorize('viewLive', $this->projectLive);

        $detail = $this->projectLive->details()->findOrFail($this->editingDetailId);

        $validated = $this->validate([
            'name' => 'nullable|string|max:255',
            'coin' => 'required|integer|min:0',
            'emptyLabel' => 'nullable|string|max:30',
            'emptyIcon' => ['nullable', 'string', Rule::in(self::EMPTY_ICON_CHOICES)],
            'hotkey' => [
                'nullable',
                'string',
                'size:1',
                Rule::unique('project_live_details', 'hotkey')
                    ->where('project_live_id', $this->projectLive->id)
                    ->ignore($detail->id),
                function ($attribute, $value, $fail) use ($detail) {
                    if (! $value) {
                        return;
                    }

                    $conflict = $this->projectLive->findHotkeyConflict($value, "seat:{$detail->id}");

                    if ($conflict) {
                        $fail("Hotkey ini sudah dipakai sebagai {$conflict} — pilih huruf/angka lain.");
                    }
                },
            ],
            'status' => 'required|in:hide,show',
            // 2048 (2MB) sebelumnya kelewat kecil buat foto HP modern — upload gagal
            // divalidasi diam-diam (cuma teks error kecil yang gampang kelewat), user
            // ngira foto-nya tidak terupload sama sekali. Dinaikkan ke 8MB.
            'img' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
        ]);

        $data = [
            'name' => $validated['name'],
            'gift_total_value' => $validated['coin'],
            'empty_label' => $validated['emptyLabel'] !== '' ? $validated['emptyLabel'] : null,
            'empty_icon' => $validated['emptyIcon'] !== '' ? $validated['emptyIcon'] : null,
            'hotkey' => $validated['hotkey'] !== '' ? $validated['hotkey'] : null,
            'status' => $validated['status'],
            // Edit manual selalu mengembalikan kursi ke source "manual", supaya tidak
            // langsung ketiban timpa oleh recalculation leaderboard auto-mode berikutnya.
            'source' => DetailSource::Manual->value,
            'project_live_gifter_id' => null,
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

        $this->dispatch('notify', message: 'Kursi berhasil disimpan.');
    }

    public function render()
    {
        return view('livewire.project-live.preview-live', [
            'details' => $this->projectLive->details,
        ]);
    }
}
