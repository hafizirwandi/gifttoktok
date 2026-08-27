{{-- Overlay bersih buat OBS Browser Source — cuma nampilin border frame, tanpa kontrol
     apa pun. Rasio kotak SELALU dari project_lives.frame_ratio_w/frame_ratio_h (bukan
     cabang per orientasi lagi) - admin bisa custom rasio apa pun lewat FrameHost,
     bukan cuma preset Portrait/Landscape/Persegi. Tidak dipatok max-width spt di
     live-show.blade.php (overlay ini murni utk OBS, bukan HP) - dibiarkan sebesar
     mungkin ngikutin viewport OBS-nya. --}}
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

    <div class="w-screen overflow-hidden flex items-center justify-center" style="height: 90vh;">
        <div style="
            width: min(100vw, 90vh * {{ $projectLive->frame_ratio_w }} / {{ $projectLive->frame_ratio_h }});
            aspect-ratio: {{ $projectLive->frame_ratio_w }} / {{ $projectLive->frame_ratio_h }};
            {{ $borderRadius }} {{ $borderStyle }}
        "></div>
    </div>
</div>
