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
        <!-- Generik utk SEMUA tata letak (bukan percabangan per mode lagi) - cols/rows/rasio
             datang dari App\Enums\DisplayMode (angka murni, bukan class Tailwind, lihat
             komentar di enum itu kenapa). Lebar dipatok maksimal 480px ("mobile-first",
             biar lanskap tidak melebar sampai memenuhi layar monitor lebar) - custom
             property --seat-w dihitung sekali (min lebar viewport, lebar hasil rasio dari
             tinggi viewport, DAN batas 480px), tinggi diturunkan darinya lewat calc() biar
             rasionya selalu konsisten (bukan dihitung independen spt sebelumnya). -->
        @php
            $mode = $projectLive->display_mode;
        @endphp
        <div class="w-screen h-screen flex items-center justify-center p-3">
            <div class="grid gap-3" style="
                --seat-w: min(100vw, 100vh * {{ $mode->ratioW() }} / {{ $mode->ratioH() }}, 480px);
                width: var(--seat-w);
                height: calc(var(--seat-w) * {{ $mode->ratioH() }} / {{ $mode->ratioW() }});
                grid-template-columns: repeat({{ $mode->cols() }}, 1fr);
                grid-template-rows: repeat({{ $mode->rows() }}, 1fr);
            ">
                @foreach ($details as $detail)
                    @include('livewire.project-live.partials.seat-box', ['detail' => $detail])
                @endforeach
            </div>
        </div>
    @endif
</div>
