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
     * CRUD hotkey warna — pencet hotkey-nya di Live, kotak kursi ganti warna (lihat
     * LiveShow::activateColorHotkey() dan partials/seat-box.blade.php). targetDetailId
     * null = GLOBAL (semua kotak kosong sekaligus), diisi = cuma satu kursi itu yang
     * berubah. CRUD lewat modal di bawah.
     */
    public bool $showModal = false;

    public ?int $editingId = null;

    public string $hotkeyInput = '';

    public string $colorInput = '#ef4444';

    public ?int $targetDetailId = null;

    /**
     * Hotkey khusus buat reset SEMUA override warna (global maupun per-kursi) balik ke
     * null — kotak kosong balik ke hitam default.
     */
    public string $defaultHotkey = '';

    public function mount(ProjectLive $projectLive): void
    {
        $this->authorize('viewLive', $projectLive);

        $this->projectLive = $projectLive;
        $this->defaultHotkey = (string) $projectLive->default_color_hotkey;
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'hotkeyInput', 'colorInput', 'targetDetailId']);
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
        $this->targetDetailId = $entry->project_live_detail_id;
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->reset(['showModal', 'editingId', 'hotkeyInput', 'colorInput', 'targetDetailId']);
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
                    $conflict = $this->projectLive->findHotkeyConflict($value, $this->editingId ? "color:{$this->editingId}" : null);

                    if ($conflict) {
                        $fail("Hotkey ini sudah dipakai sebagai {$conflict} — pilih huruf/angka lain.");
                    }
                },
            ],
            'colorInput' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'targetDetailId' => [
                'nullable',
                Rule::exists('project_live_details', 'id')->where('project_live_id', $this->projectLive->id),
            ],
        ]);

        ProjectLiveColorHotkey::updateOrCreate(
            ['id' => $this->editingId],
            [
                'project_live_id' => $this->projectLive->id,
                'project_live_detail_id' => $validated['targetDetailId'] ?: null,
                'hotkey' => strtolower($validated['hotkeyInput']),
                'color' => $validated['colorInput'],
            ]
        );

        $this->closeModal();

        $this->dispatch('notify', message: 'Hotkey warna berhasil disimpan.');
    }

    public function deleteHotkey(int $id): void
    {
        $this->authorize('viewLive', $this->projectLive);

        ProjectLiveColorHotkey::where('project_live_id', $this->projectLive->id)->whereKey($id)->delete();

        $this->dispatch('notify', message: 'Hotkey warna berhasil dihapus.');
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

                    $conflict = $this->projectLive->findHotkeyConflict($value, 'default');

                    if ($conflict) {
                        $fail("Hotkey ini sudah dipakai sebagai {$conflict} — pilih huruf/angka lain.");
                    }
                },
            ],
        ]);

        $this->projectLive->update([
            'default_color_hotkey' => $validated['defaultHotkey'] !== '' ? strtolower($validated['defaultHotkey']) : null,
        ]);

        $this->projectLive->refresh();

        $this->dispatch('notify', message: 'Hotkey default berhasil disimpan.');
    }

    /**
     * Tombol "Reset ke Default" — langsung matikan SEMUA override warna (global maupun
     * per-kursi) sekarang juga, sama seperti hotkey default kalau ditekan di Live.
     */
    public function resetActiveColor(): void
    {
        $this->authorize('viewLive', $this->projectLive);

        $this->projectLive->update(['active_hotkey_color' => null]);
        $this->projectLive->refresh();

        $this->projectLive->details()->update(['active_hotkey_color' => null]);

        $this->dispatch('notify', message: 'Warna berhasil direset ke default.');
    }

    public function render()
    {
        return view('livewire.project-live.hotkey-color', [
            'colorHotkeys' => $this->projectLive->colorHotkeys()->with('detail')->orderBy('hotkey')->get(),
            'details' => $this->projectLive->details,
        ]);
    }
}
