<?php

namespace App\Livewire\ProjectLive;

use App\Models\ProjectLive;
use App\Models\ProjectLiveColorHotkey;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Hotkey Warna')]
class HotkeyColor extends Component
{
    public ProjectLive $projectLive;

    /**
     * Bagian 1: hotkey warna GLOBAL — pencet hotkey-nya di Live, SEMUA kotak kursi yang
     * masih kosong ganti warna+bayangan bareng-bareng (lihat LiveShow::activateColorHotkey()
     * dan partials/seat-box.blade.php). CRUD lewat modal di bawah.
     */
    public bool $showModal = false;

    public ?int $editingId = null;

    public string $hotkeyInput = '';

    public string $colorInput = '#ef4444';

    /**
     * Hotkey khusus buat reset active_hotkey_color balik ke null (kotak kosong balik
     * pakai warna per-kursi masing-masing, bukan warna global lagi).
     */
    public string $defaultHotkey = '';

    /**
     * Bagian 2: warna per-kursi (dulu ada di modal Edit Kursi, sekarang dipindah ke
     * sini) — dipakai sebagai FALLBACK saat tidak ada warna hotkey global yang aktif.
     */
    public array $seatColors = [];

    public function mount(ProjectLive $projectLive): void
    {
        $this->authorize('viewLive', $projectLive);

        $this->projectLive = $projectLive;
        $this->defaultHotkey = (string) $projectLive->default_color_hotkey;

        foreach ($projectLive->details as $detail) {
            $this->seatColors[$detail->id] = $detail->empty_bg_color ?: '#000000';
        }
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'hotkeyInput', 'colorInput']);
        $this->colorInput = '#ef4444';
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $this->authorize('viewLive', $this->projectLive);

        $entry = ProjectLiveColorHotkey::where('project_live_id', $this->projectLive->id)->findOrFail($id);

        $this->editingId = $entry->id;
        $this->hotkeyInput = $entry->hotkey;
        $this->colorInput = $entry->color;
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->reset(['showModal', 'editingId', 'hotkeyInput', 'colorInput']);
        $this->resetErrorBag();
    }

    public function saveHotkey(): void
    {
        $this->authorize('viewLive', $this->projectLive);

        $validated = $this->validate([
            'hotkeyInput' => [
                'required',
                'string',
                'size:1',
                Rule::unique('project_live_color_hotkeys', 'hotkey')
                    ->where('project_live_id', $this->projectLive->id)
                    ->ignore($this->editingId),
                function ($attribute, $value, $fail) {
                    if (in_array(strtolower($value), $this->seatHotkeys(), true)) {
                        $fail('Hotkey ini sudah dipakai buat munculin salah satu kursi (1-8) — pilih huruf/angka lain.');
                    }
                },
            ],
            'colorInput' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        ProjectLiveColorHotkey::updateOrCreate(
            ['id' => $this->editingId],
            [
                'project_live_id' => $this->projectLive->id,
                'hotkey' => strtolower($validated['hotkeyInput']),
                'color' => $validated['colorInput'],
            ]
        );

        $this->closeModal();
    }

    public function deleteHotkey(int $id): void
    {
        $this->authorize('viewLive', $this->projectLive);

        ProjectLiveColorHotkey::where('project_live_id', $this->projectLive->id)->whereKey($id)->delete();
    }

    public function saveDefaultHotkey(): void
    {
        $this->authorize('viewLive', $this->projectLive);

        $validated = $this->validate([
            'defaultHotkey' => [
                'nullable',
                'string',
                'size:1',
                function ($attribute, $value, $fail) {
                    if (! $value) {
                        return;
                    }

                    $value = strtolower($value);

                    if (in_array($value, $this->seatHotkeys(), true)) {
                        $fail('Hotkey ini sudah dipakai buat munculin salah satu kursi (1-8) — pilih huruf/angka lain.');

                        return;
                    }

                    if ($this->projectLive->colorHotkeys()->where('hotkey', $value)->exists()) {
                        $fail('Hotkey ini sudah dipakai sebagai salah satu hotkey warna di atas — pilih huruf/angka lain.');
                    }
                },
            ],
        ]);

        $this->projectLive->update([
            'default_color_hotkey' => $validated['defaultHotkey'] !== '' ? strtolower($validated['defaultHotkey']) : null,
        ]);

        $this->projectLive->refresh();
    }

    /**
     * Hotkey reveal-kursi (1-8) yang sudah dipakai di project ini (huruf kecil semua)
     * — dicek supaya hotkey warna tidak diam-diam bentrok dan "menang" atas hotkey
     * reveal kursi yang sudah ada.
     *
     * @return array<int, string>
     */
    private function seatHotkeys(): array
    {
        return $this->projectLive->details()
            ->whereNotNull('hotkey')
            ->pluck('hotkey')
            ->map(fn ($hotkey) => strtolower($hotkey))
            ->all();
    }

    /**
     * Tombol "Reset ke Default" — langsung matikan override warna global sekarang juga
     * (sama seperti hotkey default kalau ditekan di Live).
     */
    public function resetActiveColor(): void
    {
        $this->authorize('viewLive', $this->projectLive);

        $this->projectLive->update(['active_hotkey_color' => null]);
        $this->projectLive->refresh();
    }

    public function saveSeatColor(int $detailId): void
    {
        $this->authorize('viewLive', $this->projectLive);

        $color = $this->seatColors[$detailId] ?? '#000000';

        $this->validate([
            'seatColors.'.$detailId => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $this->projectLive->details()->whereKey($detailId)->update(['empty_bg_color' => $color]);
    }

    public function render()
    {
        return view('livewire.project-live.hotkey-color', [
            'colorHotkeys' => $this->projectLive->colorHotkeys()->orderBy('hotkey')->get(),
            'details' => $this->projectLive->details,
        ]);
    }
}
