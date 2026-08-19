{{-- Overlay bersih buat OBS Browser Source — cuma nampilin border frame, tanpa kontrol
     apa pun. Dimensi kotaknya SENGAJA disamakan persis dengan live-show.blade.php
     (mode Vertical/Horizontal) supaya kalau dipasang berdampingan, ukurannya klop. --}}
<div wire:poll.5s="poll">
    @if ($projectLive->frame_orientation->value === 'landscape')
        <div class="min-h-screen w-screen flex items-center justify-center p-3">
            <div class="h-[50vh] aspect-[2/1]"
                style="border-radius: {{ $projectLive->frame_radius }}px; {{ $projectLive->frame_visible ? 'border: '.$projectLive->frame_border_width.'px solid '.$projectLive->frame_color.';' : '' }}">
            </div>
        </div>
    @else
        <div class="h-screen w-screen flex items-center justify-center p-3">
            <div class="h-full aspect-[1/2]"
                style="border-radius: {{ $projectLive->frame_radius }}px; {{ $projectLive->frame_visible ? 'border: '.$projectLive->frame_border_width.'px solid '.$projectLive->frame_color.';' : '' }}">
            </div>
        </div>
    @endif
</div>
