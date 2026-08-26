<?php

namespace App\Livewire\ProjectLive;

use App\Enums\FrameOrientation;
use App\Models\ProjectLive;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Frame Host')]
class FrameHost extends Component
{
    public ProjectLive $projectLive;

    /**
     * Warna & radius di-stage di sini dulu (baru disimpan lewat tombol "Simpan") supaya
     * preview di halaman ini langsung ikut berubah saat diketik/dipilih, tanpa nge-save
     * tiap ketikan. Orientasi & show/hide sebaliknya langsung tersimpan begitu diklik
     * (sama seperti tombol Tata Letak Halaman Live).
     */
    public string $color = '#38bdf8';

    public int $radius = 24;

    public int $borderWidth = 8;

    /**
     * Rasio lebar:tinggi kotak frame - klik tombol orientasi (Portrait/Landscape/
     * Persegi) cuma MENGISI dua angka ini ke preset bawaannya (lihat
     * FrameOrientation::ratioW()/ratioH() & updateOrientation()), tapi admin bebas
     * ubah manual sesudahnya lewat input "Lebar"/"Tinggi" ("custom width/height") -
     * disimpan bareng warna/radius/border lewat saveAppearance() yang sama.
     */
    public int $ratioW = 9;

    public int $ratioH = 16;

    /**
     * Efek border berkedip gonta-ganti 2-3 warna custom (bukan cuma terang-gelap dari
     * 1 warna) — lihat frame-host-live.blade.php buat animasinya. pulseColor3 boleh
     * kosong (berarti cuma 2 warna yang di-cycle).
     */
    public int $pulseSpeedMs = 1500;

    public string $pulseColor1 = '#1e3a8a';

    public string $pulseColor2 = '#38bdf8';

    public string $pulseColor3 = '';

    /**
     * Efek pulse (border kotak berkedip gonta-ganti warna) buat kotak KURSI di
     * halaman Live (beda dari border frame OBS di atas, tapi warna & kecepatannya
     * SENGAJA dipakai bareng/reuse dari pulseColor1/2/3 & pulseSpeedMs yang sama -
     * lihat live-show.blade.php & partials/seat-box.blade.php) - admin cukup
     * centang kotak MANA SAJA yang mau ikut berkedip (bisa lebih dari 1), tidak
     * perlu atur warna terpisah. Checklist-nya langsung tersimpan tiap dicentang/
     * di-uncheck (lihat updatedSeatPulsePositions()).
     */
    public bool $seatPulseEnabled = false;

    public array $seatPulsePositions = [];

    public function mount(ProjectLive $projectLive): void
    {
        $this->authorize('viewLive', $projectLive);

        $this->projectLive = $projectLive;
        $this->color = $projectLive->frame_color;
        $this->radius = $projectLive->frame_radius;
        $this->borderWidth = $projectLive->frame_border_width;
        $this->ratioW = $projectLive->frame_ratio_w;
        $this->ratioH = $projectLive->frame_ratio_h;
        $this->pulseSpeedMs = $projectLive->frame_pulse_speed_ms;
        $this->pulseColor1 = $projectLive->frame_pulse_color_1;
        $this->pulseColor2 = $projectLive->frame_pulse_color_2;
        $this->pulseColor3 = (string) $projectLive->frame_pulse_color_3;
        $this->seatPulseEnabled = $projectLive->seat_pulse_enabled;
        $this->seatPulsePositions = $projectLive->seat_pulse_positions ?? [];
    }

    public function updateOrientation(string $value): void
    {
        $this->authorize('viewLive', $this->projectLive);

        $orientation = FrameOrientation::from($value);

        $this->projectLive->update([
            'frame_orientation' => $orientation->value,
            'frame_ratio_w' => $orientation->ratioW(),
            'frame_ratio_h' => $orientation->ratioH(),
        ]);
        $this->projectLive->refresh();

        $this->ratioW = $orientation->ratioW();
        $this->ratioH = $orientation->ratioH();
    }

    public function toggleVisible(): void
    {
        $this->authorize('viewLive', $this->projectLive);

        $this->projectLive->update(['frame_visible' => ! $this->projectLive->frame_visible]);
        $this->projectLive->refresh();
    }

    public function togglePulse(): void
    {
        $this->authorize('viewLive', $this->projectLive);

        $this->projectLive->update(['frame_pulse' => ! $this->projectLive->frame_pulse]);
        $this->projectLive->refresh();
    }

    public function clearPulseColor3(): void
    {
        $this->pulseColor3 = '';
    }

    public function toggleSeatPulse(): void
    {
        $this->authorize('viewLive', $this->projectLive);

        $this->projectLive->update(['seat_pulse_enabled' => ! $this->projectLive->seat_pulse_enabled]);
        $this->projectLive->refresh();

        $this->seatPulseEnabled = $this->projectLive->seat_pulse_enabled;
    }

    /**
     * Livewire lifecycle hook - jalan otomatis begitu checkbox kursi mana pun
     * dicentang/di-uncheck (wire:model.live="seatPulsePositions", tiap checkbox
     * beda value tapi target property sama - Livewire otomatis nambah/buang value
     * itu dari/ke array), langsung tersimpan tanpa tombol "Simpan" terpisah.
     */
    public function updatedSeatPulsePositions(): void
    {
        $this->authorize('viewLive', $this->projectLive);

        $maxPosition = $this->projectLive->display_mode->seatCount();

        $validated = $this->validate([
            'seatPulsePositions' => ['array'],
            'seatPulsePositions.*' => ['integer', 'min:1', "max:{$maxPosition}"],
        ]);

        $this->projectLive->update([
            'seat_pulse_positions' => array_values(array_map('intval', $validated['seatPulsePositions'])),
        ]);
        $this->projectLive->refresh();
    }

    public function saveAppearance(): void
    {
        $this->authorize('viewLive', $this->projectLive);

        $validated = $this->validate([
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'radius' => ['required', 'integer', 'min:0', 'max:200'],
            'borderWidth' => ['required', 'integer', 'min:1', 'max:100'],
            'ratioW' => ['required', 'integer', 'min:1', 'max:100'],
            'ratioH' => ['required', 'integer', 'min:1', 'max:100'],
            'pulseSpeedMs' => ['required', 'integer', 'min:200', 'max:10000'],
            'pulseColor1' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'pulseColor2' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'pulseColor3' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $this->projectLive->update([
            'frame_color' => $validated['color'],
            'frame_radius' => $validated['radius'],
            'frame_border_width' => $validated['borderWidth'],
            'frame_ratio_w' => $validated['ratioW'],
            'frame_ratio_h' => $validated['ratioH'],
            'frame_pulse_speed_ms' => $validated['pulseSpeedMs'],
            'frame_pulse_color_1' => $validated['pulseColor1'],
            'frame_pulse_color_2' => $validated['pulseColor2'],
            'frame_pulse_color_3' => $validated['pulseColor3'] !== '' ? $validated['pulseColor3'] : null,
        ]);

        $this->projectLive->refresh();

        $this->dispatch('notify', message: 'Tampilan frame berhasil disimpan.');
    }

    public function render()
    {
        return view('livewire.project-live.frame-host');
    }
}
