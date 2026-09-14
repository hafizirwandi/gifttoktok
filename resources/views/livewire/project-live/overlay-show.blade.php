{{-- Halaman OBS Browser Source murni - nampilin animasi overlay (video WebM) yang
     lagi "playing" di antrian (App\Services\OverlayQueueService), tanpa kontrol apa
     pun. Lebar dipatok 480px ("mobile-first", sama konvensi dgn live-show.blade.php)
     & diletakkan di tengah, dikasih border kiri/kanan doang sbg PANDUAN VISUAL area
     yang nanti di-crop admin di OBS (crop dari luar border ini) - sisa layar dibiarkan
     hitam polos. --}}
<div wire:poll.1500ms="poll" class="w-screen min-h-screen bg-black flex items-center justify-center">
    <div class="relative w-full max-w-[480px] min-h-screen border-l-2 border-r-2 border-white/25 overflow-hidden flex items-center justify-center">
        @if ($current)
            @if ($current['duration_mode'] === 'auto')
                {{-- Mode "Otomatis" - lanjut ke antrian berikutnya begitu video-nya
                     SENDIRI benar2 selesai (event "ended" bawaan <video>, wire:ended
                     langsung nembak method Livewire, tidak perlu JS tambahan). --}}
                <video
                    wire:key="overlay-{{ $current['id'] }}"
                    src="{{ $current['url'] }}"
                    autoplay
                    playsinline
                    wire:ended="finishCurrent"
                    class="max-w-full max-h-screen object-contain"
                ></video>
            @else
                {{-- Mode "Manual" - dipotong paksa di duration_ms lewat JS setTimeout
                     (App\Models\OverlayAnimation::duration_ms), biarpun videonya
                     sendiri lebih panjang/looping. --}}
                <video
                    wire:key="overlay-{{ $current['id'] }}"
                    src="{{ $current['url'] }}"
                    autoplay
                    playsinline
                    x-data
                    x-init="setTimeout(() => $wire.finishCurrent(), {{ (int) $current['duration_ms'] }})"
                    class="max-w-full max-h-screen object-contain"
                ></video>
            @endif
        @endif
    </div>
</div>
