{{-- Halaman OBS Browser Source murni - nampilin animasi overlay (WebP animasi) yang
     lagi "playing" di antrian (App\Services\OverlayQueueService), tanpa kontrol apa
     pun. Lebar dipatok 480px ("mobile-first", sama konvensi dgn live-show.blade.php)
     & diletakkan di tengah, dikasih border kiri/kanan doang sbg PANDUAN VISUAL area
     yang nanti di-crop admin di OBS (crop dari luar border ini) - sisa layar dibiarkan
     hitam polos. --}}
<div wire:poll.1500ms="poll" class="w-screen min-h-screen bg-black flex items-center justify-center">
    <div class="relative w-full max-w-[480px] min-h-screen border-l-2 border-r-2 border-white/25 overflow-hidden flex items-center justify-center">
        @if ($current)
            <img
                wire:key="overlay-{{ $current['id'] }}"
                src="{{ $current['url'] }}"
                alt=""
                class="max-w-full max-h-screen object-contain"
                x-data
                x-init="setTimeout(() => $wire.finishCurrent(), {{ (int) $current['duration_ms'] }})"
            >
        @endif
    </div>
</div>
