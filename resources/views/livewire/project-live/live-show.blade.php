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
    @elseif ($projectLive->display_mode === \App\Enums\DisplayMode::Horizontal)
        <!-- Horizontal: grid 8 kursi (4 kolom x 2 baris) di tengah layar. Tinggi dipatok
             50vh (2 baris) supaya ukuran tiap kotak senada dengan mode Vertical (4 baris
             dalam 100vh = ~25vh per baris juga) — bukan lagi 100vw yang kegedean. -->
        <div class="min-h-screen w-screen flex items-center justify-center p-3">
            <div class="h-[50vh] aspect-[2/1] grid grid-cols-4 grid-rows-2 gap-3">
                @foreach ($details as $detail)
                    @include('livewire.project-live.partials.seat-box', ['detail' => $detail])
                @endforeach
            </div>
        </div>
    @else
        <!-- Vertical (default): cuma grid 8 kursi (2 kolom x 4 baris), memenuhi tinggi layar
             (100vh), diletakkan di tengah. -->
        <div class="h-screen w-screen flex items-center justify-center p-3">
            <div class="h-full aspect-[1/2] grid grid-cols-2 grid-rows-4 gap-3">
                @foreach ($details as $detail)
                    @include('livewire.project-live.partials.seat-box', ['detail' => $detail])
                @endforeach
            </div>
        </div>
    @endif
</div>
