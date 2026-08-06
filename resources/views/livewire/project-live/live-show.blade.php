<div
    x-data="{
        hotkeys: @js($details->pluck('hotkey', 'id')),
        handle(e) {
            const tag = e.target.tagName;
            if (['INPUT', 'TEXTAREA', 'SELECT'].includes(tag) || e.target.isContentEditable) return;
            if (e.repeat) return;

            const key = e.key.toLowerCase();
            const match = Object.entries(this.hotkeys).find(([id, hk]) => hk && hk.toLowerCase() === key);

            if (match) {
                $wire.call('toggleByHotkey', Number(match[0]));
            }
        },
    }"
    @keydown.window="handle($event)"
    wire:poll.visible.3s="syncFromDatabase"
    class="min-h-screen bg-black text-white"
>
    @if (! $projectLive->isLive())
        <div class="min-h-screen flex flex-col items-center justify-center gap-2 px-6 text-center">
            <div class="text-2xl">⏸</div>
            <p class="text-gray-300 font-medium">Live belum dimulai</p>
            <p class="text-gray-500 text-sm">Hubungi superadmin untuk mengaktifkan status project ini.</p>
        </div>
    @else
        <div class="flex h-screen">
            <!-- Panel kiri: area konten utama -->
            <div class="flex-1 relative bg-gradient-to-br from-gray-900 to-black flex items-center justify-center">
                <span class="text-gray-600 text-sm">{{ $projectLive->name }}</span>
            </div>

            <!-- Panel kanan: grid 2x4 kursi -->
            <div class="w-[46%] max-w-xs grid grid-cols-2 grid-rows-4 gap-1.5 p-1.5">
                @foreach ($details as $detail)
                    @if ($detail->status->value === 'show')
                        <div
                            class="relative rounded-lg overflow-hidden flex flex-col items-center justify-center"
                            style="background: radial-gradient(circle at center, {{ $detail->dominant_color }}bb 0%, #000 85%);"
                        >
                            <!-- Badge follower -->
                            <div class="absolute top-1 left-1 flex items-center gap-1 bg-black/50 rounded-full pl-0.5 pr-2 py-0.5">
                                <span class="w-3.5 h-3.5 rounded-full bg-sky-400 flex-shrink-0"></span>
                                <span class="text-[9px] font-semibold text-white leading-none">{{ $detail->follower ?: '0' }}</span>
                            </div>

                            <!-- Mic mute -->
                            <div class="absolute top-1 right-1 w-5 h-5 rounded-full bg-black/50 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="w-3 h-3 text-white/80">
                                    <path d="M12 15a3 3 0 003-3V6a3 3 0 10-6 0v6a3 3 0 003 3z" stroke="currentColor" stroke-width="1.5"/>
                                    <path d="M5 10v2a7 7 0 0014 0v-2M12 19v3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                    <path d="M4 4l16 16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                </svg>
                            </div>

                            <!-- Avatar -->
                            @if ($detail->img)
                                <img src="{{ $detail->imgUrl() }}" alt="{{ $detail->name }}" class="w-1/2 aspect-square rounded-full object-cover border border-white/10">
                            @else
                                <div class="w-1/2 aspect-square rounded-full bg-gray-700"></div>
                            @endif

                            <!-- Nama + plus -->
                            <div class="absolute bottom-1 inset-x-1 flex items-center justify-center gap-1 bg-black/50 rounded-full py-0.5 px-1.5">
                                <span class="text-[9px] font-medium text-white truncate max-w-[70%]">{{ $detail->name ?: 'Guest' }}</span>
                                <span class="w-3.5 h-3.5 rounded-full bg-white/20 flex items-center justify-center text-white text-[10px] leading-none flex-shrink-0">+</span>
                            </div>
                        </div>
                    @else
                        <div class="relative rounded-lg bg-black border border-white/5 flex flex-col items-center justify-center gap-1">
                            <div class="w-7 h-7 rounded-full border border-white/30 flex items-center justify-center text-white/70 text-base leading-none">
                                +
                            </div>
                            <span class="text-[9px] text-white/50 font-medium">Request</span>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endif
</div>
