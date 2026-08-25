<div
    x-data="{
        handle(e) {
            const tag = e.target.tagName;
            if (['INPUT', 'TEXTAREA', 'SELECT'].includes(tag) || e.target.isContentEditable) return;
            if (e.repeat) return;

            const key = e.key.toLowerCase();

            // Hotkey warna global: ganti warna+bayangan SEMUA kotak kosong sekaligus.
            if (($wire.colorHotkeys || []).includes(key)) {
                $wire.call('activateColorHotkey', key);
                return;
            }

            // Hotkey default: matikan override warna global, balik ke warna per-kursi.
            if ($wire.defaultColorHotkey && key === $wire.defaultColorHotkey) {
                $wire.call('resetColorHotkey');
                return;
            }

            // Hotkey Reset Leaderboard/Reset Coin (diatur di halaman Admin) - langsung
            // jalan begitu ditekan, tanpa konfirmasi (lihat LiveShow::triggerResetLeaderboard()).
            if ($wire.resetLeaderboardHotkey && key === $wire.resetLeaderboardHotkey) {
                $wire.call('triggerResetLeaderboard');
                return;
            }

            if ($wire.resetCoinHotkey && key === $wire.resetCoinHotkey) {
                $wire.call('triggerResetCoins');
                return;
            }

            const match = ($wire.details || []).find((d) => d.hotkey && d.hotkey.toLowerCase() === key);

            if (match) {
                $wire.call('toggleByHotkey', match.id);
            }
        },
    }"
    @keydown.window="handle($event)"
    wire:poll.{{ $projectLive->auto_gift_mode ? '1500ms' : 'visible.3s' }}="syncFromDatabase"
    class="min-h-screen bg-black text-white"
>
    @if (! $projectLive->isLive())
        <div class="min-h-screen max-w-[430px] mx-auto flex flex-col items-center justify-center gap-2 px-6 text-center">
            <div class="text-2xl">⏸</div>
            <p class="text-gray-300 font-medium">Live belum dimulai</p>
            <p class="text-gray-500 text-sm">Hubungi superadmin untuk mengaktifkan status project ini.</p>
        </div>
    @else
        <!-- Generik utk SEMUA tata letak (bukan percabangan per mode lagi) - rasio & CSS
             grid (columns/rows/areas) datang dari App\Enums\DisplayMode. Mode grid seragam
             pakai `repeat(N,1fr)`; mode "mosaik" (Kisi Dinamis dkk) pakai track custom +
             grid-template-areas (kotak per kursi ditandai grid-area:s{position} di
             partials/seat-box.blade.php). Lebar dipatok maksimal 480px ("mobile-first",
             biar lanskap tidak melebar sampai memenuhi layar monitor lebar) - custom
             property --seat-w dihitung sekali (min lebar viewport, lebar hasil rasio dari
             tinggi viewport, DAN batas 480px). Tinggi NORMALNYA diturunkan dari --seat-w
             lewat calc() biar rasionya konsisten - KECUALI mode yang intrinsicHeight()
             true (Kisi Dinamis): tingginya dibiarkan `auto`, ikut kursi persegi asli di
             dalamnya (lihat DisplayMode::intrinsicHeight()). -->
        @php
            $mode = $projectLive->display_mode;
        @endphp
        {{-- Efek pulse kursi: border 1 kotak kursi tertentu berkedip gonta-ganti warna
             (lihat App\Livewire\ProjectLive\DetailAdmin::saveSeatPulse() & posisi kotak
             yg cocok ditandai di partials/seat-box.blade.php) - keyframe-nya cukup 1x
             di sini (dipakai oleh SATU kotak yg posisinya cocok), bukan per-kotak. --}}
        @if ($projectLive->seat_pulse_enabled)
            <style>
                @keyframes gtt-seat-pulse {
                    0% { border-color: {{ $projectLive->seat_pulse_color_1 }}; }
                    @if ($projectLive->seat_pulse_color_3)
                        33% { border-color: {{ $projectLive->seat_pulse_color_2 }}; }
                        66% { border-color: {{ $projectLive->seat_pulse_color_3 }}; }
                    @else
                        50% { border-color: {{ $projectLive->seat_pulse_color_2 }}; }
                    @endif
                    100% { border-color: {{ $projectLive->seat_pulse_color_1 }}; }
                }
            </style>
        @endif
        <div class="w-screen h-screen flex items-center justify-center p-3">
            <div class="grid" style="
                --seat-w: min(100vw, 100vh * {{ $mode->ratioW() }} / {{ $mode->ratioH() }}, 480px);
                width: var(--seat-w);
                height: {{ $mode->intrinsicHeight() ? 'auto' : 'calc(var(--seat-w) * '.$mode->ratioH().' / '.$mode->ratioW().')' }};
                grid-template-columns: {{ $mode->gridTemplateColumns() }};
                grid-template-rows: {{ $mode->gridTemplateRows() }};
                gap: {{ $projectLive->seat_gap }}px;
                @if ($mode->gridTemplateAreas()) grid-template-areas: {{ $mode->gridTemplateAreas() }}; @endif
            ">
                @foreach ($details as $detail)
                    @include('livewire.project-live.partials.seat-box', ['detail' => $detail, 'mode' => $mode])
                @endforeach
            </div>
        </div>
    @endif
</div>
