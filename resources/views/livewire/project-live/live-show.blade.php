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
    class="bg-black text-white overflow-hidden"
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
        {{-- Efek pulse kursi: border kotak kursi yang DICENTANG (bisa lebih dari 1
             sekaligus) berkedip gonta-ganti warna (dicek per-kotak di
             partials/seat-box.blade.php via seat_pulse_positions, diatur lewat
             App\Livewire\ProjectLive\FrameHost::updatedSeatPulsePositions() - menu
             "Frame Host", bukan Admin) - warna & kecepatannya SENGAJA dari
             frame_pulse_* (settingan Efek Pulse border frame yang sama), biar admin
             tidak perlu atur ulang warna, cukup centang kotak mana yang berkedip.
             Keyframe-nya cukup 1x di sini (dipakai semua kotak yg posisinya
             tercentang), bukan per-kotak. --}}
        @if ($projectLive->seat_pulse_enabled)
            <style>
                @keyframes gtt-seat-pulse {
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
        <div class="w-screen overflow-hidden flex items-start justify-center px-3 pt-4" style="height: 97vh;">
            {{-- Kotak mobile-first max 480px ini SATU-SATUNYA acuan ukuran/posisi baik utk
                 grid kursi MAUPUN BG layar penuh - BG SENGAJA dibatasi di dalam kotak ini
                 (position:relative di sini), BUKAN selebar layar PC (w-screen di wrapper
                 luar itu cuma buat nge-center kotak ini, bukan area BG). --}}
            <div style="
                --seat-w: min(100vw, 93vh * {{ $mode->ratioW() }} / {{ $mode->ratioH() }}, 480px);
                width: var(--seat-w);
                height: {{ $mode->intrinsicHeight() ? 'auto' : 'calc(var(--seat-w) * '.$mode->ratioH().' / '.$mode->ratioW().')' }};
                position: relative;
            ">
                {{-- BG layar penuh (App\Livewire\ProjectLive\Background, placement=screen) -
                     SENGAJA di belakang grid kursi (position:absolute + z-index:0 vs grid yg
                     z-index:1), bukan overlay penutup. Video autoplay+loop krn ini browser
                     source OBS/Chromium, aman diputar otomatis tanpa interaksi user - muted
                     ikut audio_enabled (halaman Live ASLI ini yang boleh keluar suara, beda
                     dari preview-live.blade.php yang selalu dipaksa senyap). --}}
                @if ($screenBackground)
                    @php
                        $screenFit = \App\Enums\BackgroundFit::from($screenBackground['fit_mode'])->cssObjectFit();
                        $screenVideoMuted = ! ($screenBackground['audio_enabled'] ?? false);
                        // Sama kayak App\...\partials\seat-box.blade.php: attribute HTML "muted"
                        // cuma dibaca browser SEKALI pas <video> pertama kali di-parse - toggle
                        // audio_enabled belakangan yang cuma PATCH attribute lewat wire:poll tidak
                        // pernah benar2 ke-apply ke video yang udah kepalang jalan. wire:key ikut
                        // berubah nilai kalau status mute-nya berubah, biar Livewire bikin ulang
                        // elemennya dari nol.
                        $screenVideoKey = 'screenbg-'.($screenVideoMuted ? 'muted' : 'unmuted');
                    @endphp
                    <div style="position: absolute; inset: 0; z-index: 0; overflow: hidden;">
                        @if ($screenBackground['type'] === 'video')
                            <video wire:key="{{ $screenVideoKey }}" src="{{ $screenBackground['url'] }}" autoplay loop playsinline {{ $screenVideoMuted ? 'muted' : '' }}
                                style="width: 100%; height: 100%; object-fit: {{ $screenFit }}; transform: translate({{ $screenBackground['offset_x'] }}px, {{ $screenBackground['offset_y'] }}px) scale({{ $screenBackground['scale'] / 100 }});"></video>
                        @else
                            <img src="{{ $screenBackground['url'] }}" alt=""
                                style="width: 100%; height: 100%; object-fit: {{ $screenFit }}; transform: translate({{ $screenBackground['offset_x'] }}px, {{ $screenBackground['offset_y'] }}px) scale({{ $screenBackground['scale'] / 100 }});">
                        @endif
                    </div>
                @endif
                <div class="grid" style="
                    width: 100%;
                    height: 100%;
                    grid-template-columns: {{ $mode->gridTemplateColumns() }};
                    grid-template-rows: {{ $mode->gridTemplateRows() }};
                    gap: {{ $projectLive->seat_gap }}px;
                    position: relative;
                    z-index: 1;
                    @if ($mode->gridTemplateAreas()) grid-template-areas: {{ $mode->gridTemplateAreas() }}; @endif
                ">
                    @foreach ($details as $detail)
                        @include('livewire.project-live.partials.seat-box', ['detail' => $detail, 'mode' => $mode, 'allowAudio' => true])
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>
