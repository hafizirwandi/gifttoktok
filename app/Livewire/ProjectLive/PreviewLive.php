<?php

namespace App\Livewire\ProjectLive;

use App\Enums\DetailSource;
use App\Enums\DetailStatus;
use App\Enums\SeatFont;
use App\Models\ProjectLive;
use App\Models\ProjectLiveDetail;
use App\Services\DominantColorExtractor;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.preview')]
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

    /**
     * Path GAMBAR yang lagi tersimpan di kursi ini (bukan file baru yang mau di-upload,
     * lihat $emptyIconFile utk itu) - dulu ini emoji yang dipilih dari daftar tetap,
     * sekarang diganti total jadi upload per kursi (lihat App\Models\ProjectLiveDetail::
     * emptyIconUrl()). String kosong = belum ada, fallback ke '+' bawaan.
     */
    public string $emptyIcon = '';

    public $emptyIconFile = null;

    public string $hotkey = '';

    public string $status = 'hide';

    public bool $micEnabled = true;

    /**
     * Font teks kotak kosong (App\Enums\SeatFont) & warna border kustom kursi ini -
     * lihat App\Models\ProjectLiveDetail::font/border_color. borderColor string kosong
     * = pakai default bawaan (border-white/15), bukan hitam/putih literal.
     */
    public string $font = 'default';

    public string $borderColor = '';

    /**
     * BG layar penuh yang lagi aktif - sama persis dgn App\Livewire\ProjectLive\
     * LiveShow::$screenBackground, dipakai biar Preview menampilkan BG juga.
     *
     * @var array<string, mixed>|null
     */
    public ?array $screenBackground = null;

    public function mount(ProjectLive $projectLive): void
    {
        $this->authorize('viewLive', $projectLive);

        $this->projectLive = $projectLive;
        $this->loadScreenBackground();
    }

    private function loadScreenBackground(): void
    {
        $bg = $this->projectLive->activeScreenBackground();

        $this->screenBackground = $bg ? $bg->toLiveArray() : null;
    }

    public function hideAll(): void
    {
        $this->authorize('viewLive', $this->projectLive);

        $this->projectLive->details()->update(['status' => DetailStatus::Hide->value]);
    }

    public function showAll(): void
    {
        $this->authorize('viewLive', $this->projectLive);

        $this->projectLive->details()->update(['status' => DetailStatus::Show->value]);
    }

    public function openEdit(int $detailId): void
    {
        $detail = $this->projectLive->details()->findOrFail($detailId);

        $this->editingDetailId = $detail->id;
        $this->name = (string) $detail->name;
        $this->coin = (string) $detail->gift_total_value;
        $this->emptyLabel = (string) $detail->empty_label;
        $this->emptyIcon = (string) $detail->empty_icon;
        $this->emptyIconFile = null;
        $this->hotkey = (string) $detail->hotkey;
        $this->status = $detail->status->value;
        $this->micEnabled = $detail->mic_visible;
        $this->font = $detail->font?->value ?? SeatFont::Default->value;
        $this->borderColor = (string) $detail->border_color;
        $this->img = null;
    }

    public function closeEdit(): void
    {
        $this->reset(['editingDetailId', 'img', 'name', 'coin', 'emptyLabel', 'emptyIcon', 'emptyIconFile', 'hotkey', 'status', 'micEnabled', 'font', 'borderColor']);
    }

    /**
     * URL preview icon kotak kosong yang LAGI TERSIMPAN (bukan file baru yang belum
     * di-upload) - dipakai modal edit buat nampilin thumbnail sebelum Simpan.
     */
    public function emptyIconUrl(): ?string
    {
        return $this->emptyIcon !== '' ? Storage::disk('public')->url($this->emptyIcon) : null;
    }

    /**
     * Hapus icon kotak kosong yang lagi tersimpan, balik ke fallback default ('+') -
     * langsung tereksekusi (beda dari upload baru yang nunggu tombol Simpan).
     */
    public function removeEmptyIcon(): void
    {
        $this->authorize('viewLive', $this->projectLive);

        $detail = $this->projectLive->details()->findOrFail($this->editingDetailId);

        if ($detail->empty_icon) {
            Storage::disk('public')->delete($detail->empty_icon);
        }

        $detail->update(['empty_icon' => null]);

        $this->emptyIcon = '';
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

    public function toggleModalMic(): void
    {
        $this->micEnabled = ! $this->micEnabled;
    }

    public function save(): void
    {
        $this->authorize('viewLive', $this->projectLive);

        $detail = $this->projectLive->details()->findOrFail($this->editingDetailId);

        $validated = $this->validate([
            'name' => 'nullable|string|max:255',
            'coin' => 'required|integer|min:0',
            'emptyLabel' => 'nullable|string|max:30',
            'emptyIconFile' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
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
            'micEnabled' => 'boolean',
            'font' => ['required', Rule::in(array_column(SeatFont::cases(), 'value'))],
            'borderColor' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            // 2048 (2MB) sebelumnya kelewat kecil buat foto HP modern — upload gagal
            // divalidasi diam-diam (cuma teks error kecil yang gampang kelewat), user
            // ngira foto-nya tidak terupload sama sekali. Dinaikkan ke 8MB.
            'img' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
        ]);

        $data = [
            'name' => $validated['name'],
            'gift_total_value' => $validated['coin'],
            'empty_label' => $validated['emptyLabel'] !== '' ? $validated['emptyLabel'] : null,
            'hotkey' => $validated['hotkey'] !== '' ? $validated['hotkey'] : null,
            'status' => $validated['status'],
            'mic_visible' => $validated['micEnabled'],
            'font' => $validated['font'] !== SeatFont::Default->value ? $validated['font'] : null,
            'border_color' => $validated['borderColor'] !== '' ? $validated['borderColor'] : null,
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

        if ($this->emptyIconFile) {
            $oldEmptyIcon = $detail->empty_icon;

            $data['empty_icon'] = $this->emptyIconFile->store('project-live-details/'.$this->projectLive->id.'/empty-icons', 'public');

            if ($oldEmptyIcon) {
                Storage::disk('public')->delete($oldEmptyIcon);
            }
        }

        $detail->update($data);

        $this->closeEdit();

        $this->dispatch('notify', message: 'Kursi berhasil disimpan.');
    }

    public function render()
    {
        // Dibentuk lewat ProjectLiveDetail::toLiveArray() yang SAMA PERSIS dgn
        // App\Livewire\ProjectLive\LiveShow - lihat komentar method itu kenapa,
        // ini yang bikin preview-live.blade.php bisa pakai partials/seat-box.blade.php
        // yang sama dan otomatis menampilkan background/font/warna border PERSIS
        // spt yang bakal tampil di halaman Live sungguhan.
        $details = $this->projectLive->details()
            ->with('background')
            ->orderBy('position')
            ->get()
            ->map(fn (ProjectLiveDetail $detail) => $detail->toLiveArray());

        return view('livewire.project-live.preview-live', [
            'details' => $details,
        ]);
    }
}
