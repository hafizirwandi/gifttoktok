{{-- Overlay bersih buat OBS Browser Source — cuma nampilin border frame, tanpa kontrol
     apa pun. Dimensi kotaknya SENGAJA disamakan persis dengan live-show.blade.php
     (mode Vertical/Horizontal) supaya kalau dipasang berdampingan, ukurannya klop. --}}
<div wire:poll.5s="poll">
    @php
        $borderRadius = 'border-radius: '.$projectLive->frame_radius.'px;';
        $borderStyle = '';

        if ($projectLive->frame_visible) {
            $borderStyle = $projectLive->frame_pulse
                ? 'border: '.$projectLive->frame_border_width.'px solid '.$projectLive->frame_pulse_color_1.'; animation: gtt-frame-pulse '.$projectLive->frame_pulse_speed_ms.'ms ease-in-out infinite;'
                : 'border: '.$projectLive->frame_border_width.'px solid '.$projectLive->frame_color.';';
        }
    @endphp

    @if ($projectLive->frame_visible && $projectLive->frame_pulse)
        {{-- Border berkedip gonta-ganti 2-3 warna custom (bukan cuma terang-gelap dari
             1 warna) — lihat App\Livewire\ProjectLive\FrameHost. --}}
        <style>
            @keyframes gtt-frame-pulse {
                0% { border-color: {{ $projectLive->frame_pulse_color_1 }}; }
                @if ($projectLive->frame_pulse_color_3)
                    33% { border-color: {{ $projectLive->frame_pulse_color_2 }}; }
                    66% { border-color: {{ $projectLive->frame_pulse_color_3 }}; }
                @else
                    50% { border-color: {{ $projectLive->frame_pulse_color_2 }}; }
                @endif
                100% { border-color: {{ $projectLive->frame_pulse_color_1 }}; }
            }
        </style>
    @endif

    @if ($projectLive->frame_orientation->value === 'landscape')
        <div class="min-h-screen w-screen flex items-center justify-center p-3">
            <div class="h-[50vh] aspect-[2/1]" style="{{ $borderRadius }} {{ $borderStyle }}"></div>
        </div>
    @else
        <div class="h-screen w-screen flex items-center justify-center p-3">
            <div class="h-full aspect-[1/2]" style="{{ $borderRadius }} {{ $borderStyle }}"></div>
        </div>
    @endif
</div>
