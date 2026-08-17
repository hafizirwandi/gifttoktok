<div
    x-data="{
        handle(e) {
            const tag = e.target.tagName;
            if (['INPUT', 'TEXTAREA', 'SELECT'].includes(tag) || e.target.isContentEditable) return;
            if (e.repeat) return;

            const key = e.key.toLowerCase();
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
        <div class="max-w-[860px] mx-auto my-6">
            <div class="flex items-stretch">
                <!-- Panel kiri: area konten utama -->
                <div class="flex-1 relative bg-gradient-to-br from-gray-900 to-black flex items-center justify-center">
                    <span class="text-gray-600 text-sm">{{ $projectLive->name }}</span>
                </div>

                <!-- Panel kanan: grid 2x4 kursi, tiap kotak dipaksa persegi (aspect-square) -->
                <div class="w-[46%] grid grid-cols-2 gap-3 p-3 content-start">
                    @foreach ($details as $detail)
                        @if ($detail['status'] === 'show' && $detail['name'])
                            <div wire:key="seat-{{ $detail['id'] }}" wire:click="toggleClick({{ $detail['id'] }})"
                                class="relative aspect-square rounded-xl overflow-hidden border-4 border-white/15 cursor-pointer">
                                <!-- Background: foto yang sama, diblur & digelapkan sedikit -->
                                @if ($detail['img_url'])
                                    <img src="{{ $detail['img_url'] }}" alt="" aria-hidden="true"
                                        class="absolute inset-0 w-full h-full object-cover scale-125 blur-md brightness-[0.45]">
                                @else
                                    <div class="absolute inset-0" style="background: {{ $detail['dominant_color'] }};"></div>
                                @endif

                                <!-- Avatar -->
                                <div class="relative h-full flex items-center justify-center">
                                    @if ($detail['img_url'])
                                        <img src="{{ $detail['img_url'] }}" alt="{{ $detail['name'] }}" class="w-[62%] aspect-square rounded-full object-cover ring-2 ring-white/20">
                                    @else
                                        <div class="w-[62%] aspect-square rounded-full bg-gray-700"></div>
                                    @endif
                                </div>

                                <!-- Badge follower: bintang biru + angka -->
                                <div class="absolute top-2 left-2 flex items-center gap-1 bg-black/60 rounded-full pl-0.5 pr-1.5 py-0.5">
                                    <span class="w-4 h-4 rounded-full bg-sky-400 border-2 border-white flex items-center justify-center flex-shrink-0">
                                        <svg viewBox="0 0 20 20" fill="currentColor" class="w-2.5 h-2.5 text-white">
                                            <path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401Z" clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                    <span class="text-xs font-semibold text-white leading-none">{{ $detail['gift_total_value'] > 0 ? number_format($detail['gift_total_value']) : ($detail['follower'] ?: '0') }}</span>
                                </div>

                                <!-- Badge nama + plus -->
                                <div class="absolute bottom-2 left-2 h-7 flex items-center gap-1 bg-black/60 rounded-full py-0.5 pl-1.5 pr-0.5">
                                    <span class="text-xs font-medium text-white truncate max-w-[8ch]">{{ $detail['name'] }}</span>
                                    <span class="w-4 h-4 rounded-full bg-white/20 flex items-center justify-center text-white text-xs leading-none flex-shrink-0">+</span>
                                </div>

                                <!-- Mic mute: pojok kanan bawah, transparan tanpa badge -->
                                <div class="absolute bottom-2 right-2 h-10 flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="w-5 h-5 text-white/80 drop-shadow">
                                        <path d="M12 15a3 3 0 003-3V6a3 3 0 10-6 0v6a3 3 0 003 3z" stroke="currentColor" stroke-width="1.5"/>
                                        <path d="M5 10v2a7 7 0 0014 0v-2M12 19v3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                        <path d="M4 4l16 16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                    </svg>
                                </div>
                            </div>
                        @else
                            <div wire:key="seat-{{ $detail['id'] }}" wire:click="toggleClick({{ $detail['id'] }})"
                                class="relative aspect-square rounded-xl bg-black border-4 border-white/10 flex flex-col items-center justify-center gap-2 cursor-pointer">
                                <div class="text-white/70 text-4xl leading-none">
                                    +
                                </div>
                                <span class="text-lg text-white/50 font-medium">Request</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>
