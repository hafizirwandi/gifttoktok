<div class="bg-black text-white min-h-screen overflow-hidden">
    {{-- Bar kontrol tipis mengambang di atas - halaman ini SENGAJA fullscreen tanpa
         header (layouts.live, sama seperti live-show.blade.php) supaya preview-nya
         benar-benar mirip tampilan Live asli, bar ini cuma overlay tipis di atasnya. --}}
    <div class="fixed top-0 inset-x-0 z-20 flex items-center justify-between gap-3 px-4 py-2 bg-black/70 backdrop-blur-sm text-xs">
        <a href="{{ route('project-live.admin', $projectLive) }}" wire:navigate
            class="text-indigo-400 hover:underline flex-shrink-0">&larr; Admin</a>
        <p class="text-gray-400 truncate">
            @if ($projectLive->auto_gift_mode)
                Otomatis dari gift TikTok LIVE - edit manual bisa ketiban update berikutnya.
            @else
                {{ $details->count() }} kursi - klik kotak utk edit.
            @endif
        </p>
        <div class="flex-shrink-0 flex items-center gap-2">
            <button wire:click="showAll" wire:confirm="Tampilkan semua kursi?" type="button"
                class="inline-flex items-center px-2.5 py-1 bg-green-600 text-white font-semibold rounded-md hover:bg-green-700">
                Show All
            </button>
            <button wire:click="hideAll" wire:confirm="Sembunyikan semua kursi?" type="button"
                class="inline-flex items-center px-2.5 py-1 bg-gray-700 text-white font-semibold rounded-md hover:bg-gray-600">
                Hide All
            </button>
        </div>
    </div>

    @php
        $mode = $projectLive->display_mode;
    @endphp
    <div class="w-screen overflow-hidden flex items-start justify-center px-3 pt-20" style="height: 97vh;">
        {{-- Kotak mobile-first max 480px ini SATU-SATUNYA acuan ukuran/posisi baik utk
             grid kursi MAUPUN BG layar penuh - SAMA PERSIS dgn live-show.blade.php,
             lihat komentar di sana. --}}
        <div style="
            --seat-w: min(100vw, 93vh * {{ $mode->ratioW() }} / {{ $mode->ratioH() }}, 480px);
            width: var(--seat-w);
            height: {{ $mode->intrinsicHeight() ? 'auto' : 'calc(var(--seat-w) * '.$mode->ratioH().' / '.$mode->ratioW().')' }};
            position: relative;
        ">
            @if ($screenBackground)
                @php $screenFit = \App\Enums\BackgroundFit::from($screenBackground['fit_mode'])->cssObjectFit(); @endphp
                <div style="position: absolute; inset: 0; z-index: 0; overflow: hidden;">
                    @if ($screenBackground['type'] === 'video')
                        <video src="{{ $screenBackground['url'] }}" autoplay loop muted playsinline
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
                    @php
                        $seatStyle = ($mode->gridTemplateAreas() ? 'grid-area: s'.$detail['position'].';' : '')
                            .($mode->seatStyleOverrides()[$detail['position']] ?? '')
                            .(($detail['border_color'] ?? null) ? ' border-color: '.$detail['border_color'].';' : '');
                    @endphp

                    @if ($detail['background'] ?? null)
                        {{-- Kotak ini jadi BG custom (App\Livewire\ProjectLive\Background) - diedit
                             lewat halaman Background, bukan di sini, jadi tidak diklik. --}}
                        @php $seatFit = \App\Enums\BackgroundFit::from($detail['background']['fit_mode'])->cssObjectFit(); @endphp
                        <div style="{{ $seatStyle }}"
                            class="relative w-full h-full rounded-xl overflow-hidden border border-gray-700">
                            @if ($detail['background']['type'] === 'video')
                                <video src="{{ $detail['background']['url'] }}" autoplay loop muted playsinline
                                    style="width: 100%; height: 100%; object-fit: {{ $seatFit }}; transform: translate({{ $detail['background']['offset_x'] }}px, {{ $detail['background']['offset_y'] }}px) scale({{ $detail['background']['scale'] / 100 }});"></video>
                            @else
                                <img src="{{ $detail['background']['url'] }}" alt=""
                                    style="width: 100%; height: 100%; object-fit: {{ $seatFit }}; transform: translate({{ $detail['background']['offset_x'] }}px, {{ $detail['background']['offset_y'] }}px) scale({{ $detail['background']['scale'] / 100 }});">
                            @endif
                            <span class="absolute top-1.5 left-1.5 text-[10px] font-semibold px-1.5 py-0.5 rounded bg-black/60 text-white">
                                #{{ $detail['position'] }} &middot; BG
                            </span>
                        </div>
                    @else
                        <div wire:click="openEdit({{ $detail['id'] }})" role="button" tabindex="0"
                            style="{{ $seatStyle }}"
                            class="relative w-full h-full rounded-xl overflow-hidden bg-gray-900 flex flex-col items-center justify-center gap-1 hover:ring-2 hover:ring-indigo-500 transition cursor-pointer {{ $detail['is_pinned'] ? 'border-2 border-amber-500' : 'border border-gray-700' }}">
                            {{-- Pin (App\Services\GiftLeaderboardService) - datanya dikunci, tidak ikut
                                 ter-reset/ditimpa auto-gift selama pin aktif. Border kotak & badge ini
                                 SENGAJA lebih mencolok (amber) drpd indikator lain, biar status "aktif
                                 dikunci" langsung kelihatan sekilas tanpa perlu buka modal. --}}
                            @if ($detail['is_pinned'])
                                <span class="absolute bottom-1.5 left-1.5 z-10 flex items-center gap-0.5 text-[8px] font-semibold px-1 py-0.5 rounded-full bg-amber-500 text-black shadow" title="Di-pin - tidak ikut reset/auto-gift">
                                    📌 PIN
                                </span>
                            @endif
                            <span class="absolute top-1.5 left-1.5 flex items-center gap-1">
                                <span class="text-[7px] font-semibold px-1 py-0.5 rounded bg-black/60 text-white">
                                    #{{ $detail['position'] }}
                                </span>
                                @php
                                    $previewColor = $detail['status'] === 'show' ? $detail['dominant_color'] : ($detail['active_hotkey_color'] ?: '#000000');
                                @endphp
                                <span class="w-3 h-3 rounded-full border border-white/60 shadow" style="background: {{ $previewColor }};" title="{{ $previewColor }}"></span>
                            </span>

                            <!-- Toggle status: klik langsung ubah tanpa buka modal -->
                            <button type="button" wire:click.stop="toggleStatus({{ $detail['id'] }})"
                                title="{{ $detail['status'] === 'show' ? 'Klik untuk Hide' : 'Klik untuk Show' }}"
                                class="absolute top-1.5 right-1.5 flex items-center gap-0.5 rounded-full px-0.5 py-0.5 transition {{ $detail['status'] === 'show' ? 'bg-green-600' : 'bg-gray-600' }}">
                                <span class="relative inline-flex h-2.5 w-4 items-center rounded-full bg-black/20">
                                    <span class="inline-block h-1.5 w-1.5 transform rounded-full bg-white transition {{ $detail['status'] === 'show' ? 'translate-x-1.5' : 'translate-x-0.5' }}"></span>
                                </span>
                                <span class="text-[7px] font-semibold text-white pr-0.5">{{ $detail['status'] === 'show' ? 'Show' : 'Hide' }}</span>
                            </button>

                            @if ($detail['img_url'])
                                <img src="{{ $detail['img_url'] }}" class="w-12 h-12 rounded-full object-cover" alt="{{ $detail['name'] }}">
                            @else
                                <div class="w-12 h-12 rounded-full bg-gray-700 flex items-center justify-center text-gray-400 text-lg">
                                    +
                                </div>
                            @endif

                            <div class="flex flex-col items-center gap-0 leading-tight mt-1">
                                <span class="text-[9px] font-medium text-gray-300 truncate max-w-[90%]">
                                    {{ $detail['name'] ?: 'Belum diisi' }}
                                </span>

                                <span class="text-[8px] text-gray-500">
                                    {{ number_format($detail['gift_total_value']) }} coin
                                </span>
                            </div>

                            @if ($detail['hotkey'])
                                <span class="absolute bottom-1.5 right-1.5 text-[10px] font-mono px-1.5 py-0.5 rounded bg-indigo-600 text-white">
                                    {{ $detail['hotkey'] }}
                                </span>
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    <!-- Modal Edit Kursi -->
    <div x-show="$wire.editingDetailId !== null" x-cloak
        class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div x-show="$wire.editingDetailId !== null" x-transition.opacity wire:click="closeEdit"
                class="fixed inset-0 bg-black/60"></div>

            <div x-show="$wire.editingDetailId !== null" x-transition
                class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6 space-y-4 text-gray-900 dark:text-gray-100">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    Edit Kursi
                </h3>

                <form wire:submit="save" class="space-y-4">
                    <div x-data="{ preview: null }">
                        <x-input-label for="img" value="Foto" />
                        <input type="file" wire:model="img" id="img" accept="image/*"
                            x-on:change="
                                const file = $event.target.files[0];
                                if (! file) { preview = null; return; }
                                const reader = new FileReader();
                                reader.onload = (e) => preview = e.target.result;
                                reader.readAsDataURL(file);
                            "
                            class="block mt-1 w-full text-sm text-gray-600 dark:text-gray-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 dark:file:bg-indigo-900/40 file:text-indigo-700 dark:file:text-indigo-300">
                        <p class="text-xs text-gray-400 mt-1">JPG, PNG, atau WEBP, maksimal 8MB.</p>
                        <div wire:loading wire:target="img" class="text-xs text-gray-400 mt-1">Mengunggah...</div>
                        <template x-if="preview">
                            <img :src="preview" class="w-16 h-16 rounded-full object-cover mt-2">
                        </template>
                        <x-input-error :messages="$errors->get('img')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="name" value="Nama" />
                        <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="hotkey" value="Hotkey" />
                        <x-text-input wire:model="hotkey" id="hotkey" maxlength="1" class="block mt-1 w-full" type="text" placeholder="1" />
                        <x-input-error :messages="$errors->get('hotkey')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="coin" value="Coin" />
                        <x-text-input wire:model="coin" id="coin" class="block mt-1 w-full" type="number" min="0" placeholder="0" />
                        <p class="text-xs text-gray-400 mt-1">Angka gift/coin yang tampil di badge kursi ini.</p>
                        <x-input-error :messages="$errors->get('coin')" class="mt-2" />
                    </div>

                    <div class="border-t border-gray-100 dark:border-gray-700 pt-4 space-y-3">
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400">Tampilan kotak ini saat kosong (belum ada gifter/tamu)</p>

                        <div>
                            <x-input-label for="emptyLabel" value="Teks" />
                            <x-text-input wire:model="emptyLabel" id="emptyLabel" class="block mt-1 w-full" type="text" placeholder="Request" maxlength="30" />
                            <x-input-error :messages="$errors->get('emptyLabel')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label value="Font Teks" />
                            <div class="grid grid-cols-2 gap-1.5 mt-1">
                                @foreach (\App\Enums\SeatFont::cases() as $option)
                                    <button type="button" wire:click="$set('font', '{{ $option->value }}')"
                                        style="font-family: {{ $option->cssFontFamily() }};"
                                        class="px-2 py-1.5 text-sm rounded-md border transition {{ $font === $option->value ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300' : 'border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                                        {{ $option->label() }}
                                    </button>
                                @endforeach
                            </div>
                            <x-input-error :messages="$errors->get('font')" class="mt-2" />
                        </div>

                        <div x-data="{ preview: null }">
                            <x-input-label for="emptyIconFile" value="Icon" />
                            <div class="flex items-center gap-2 mt-1">
                                @if ($this->emptyIconUrl())
                                    <img src="{{ $this->emptyIconUrl() }}" class="w-9 h-9 object-contain rounded-md border border-gray-200 dark:border-gray-600 flex-shrink-0">
                                @endif
                                <template x-if="preview">
                                    <img :src="preview" class="w-9 h-9 object-contain rounded-md border border-gray-200 dark:border-gray-600 flex-shrink-0">
                                </template>
                                <input type="file" wire:model="emptyIconFile" id="emptyIconFile" accept="image/*"
                                    x-on:change="
                                        const file = $event.target.files[0];
                                        if (! file) { preview = null; return; }
                                        const reader = new FileReader();
                                        reader.onload = (e) => preview = e.target.result;
                                        reader.readAsDataURL(file);
                                    "
                                    class="block w-full text-sm text-gray-600 dark:text-gray-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 dark:file:bg-indigo-900/40 file:text-indigo-700 dark:file:text-indigo-300">
                                @if ($this->emptyIconUrl())
                                    <button type="button" wire:click="removeEmptyIcon"
                                        class="flex-shrink-0 text-xs font-semibold text-gray-400 hover:text-red-500">
                                        Hapus
                                    </button>
                                @endif
                            </div>
                            <p class="text-xs text-gray-400 mt-1">JPG, PNG, atau WEBP, maksimal 8MB. Kosongkan (jangan pilih file) untuk fallback default (+).</p>
                            <div wire:loading wire:target="emptyIconFile" class="text-xs text-gray-400 mt-1">Mengunggah...</div>
                            <x-input-error :messages="$errors->get('emptyIconFile')" class="mt-2" />
                        </div>

                        <p class="text-xs text-gray-400">
                            Warna latar kotak kosong sekarang diatur di halaman
                            <a href="{{ route('project-live.hotkey-color', $projectLive) }}" wire:navigate class="text-indigo-600 dark:text-indigo-400 hover:underline">Hotkey Warna</a>.
                        </p>
                    </div>

                    <div class="border-t border-gray-100 dark:border-gray-700 pt-4">
                        <x-input-label for="borderColor" value="Warna Border Kotak" />
                        <div class="flex items-center gap-2 mt-1">
                            <input type="color" wire:model="borderColor" id="borderColor"
                                value="{{ $borderColor ?: '#ffffff' }}"
                                class="h-9 w-14 rounded-md border border-gray-200 dark:border-gray-600 cursor-pointer">
                            <x-text-input wire:model="borderColor" class="block w-full" type="text" placeholder="Default (#ffffff26)" />
                            @if ($borderColor)
                                <button type="button" wire:click="$set('borderColor', '')"
                                    class="flex-shrink-0 text-xs font-semibold text-gray-400 hover:text-red-500">
                                    Reset
                                </button>
                            @endif
                        </div>
                        <x-input-error :messages="$errors->get('borderColor')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label value="Status" />
                        <button type="button" wire:click="toggleModalStatus"
                            class="mt-1 inline-flex items-center gap-2 rounded-full pl-1 pr-3 py-1 transition {{ $status === 'show' ? 'bg-green-600' : 'bg-gray-300 dark:bg-gray-600' }}">
                            <span class="relative inline-flex h-6 w-11 items-center rounded-full bg-black/20">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition {{ $status === 'show' ? 'translate-x-6' : 'translate-x-1' }}"></span>
                            </span>
                            <span class="text-sm font-medium text-white">{{ $status === 'show' ? 'Show' : 'Hide' }}</span>
                        </button>
                        <x-input-error :messages="$errors->get('status')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label value="Icon Mic" />
                        <button type="button" wire:click="toggleModalMic"
                            class="mt-1 inline-flex items-center gap-2 rounded-full pl-1 pr-3 py-1 transition {{ $micEnabled ? 'bg-green-600' : 'bg-gray-300 dark:bg-gray-600' }}">
                            <span class="relative inline-flex h-6 w-11 items-center rounded-full bg-black/20">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition {{ $micEnabled ? 'translate-x-6' : 'translate-x-1' }}"></span>
                            </span>
                            <span class="text-sm font-medium text-white">{{ $micEnabled ? 'Tampil' : 'Sembunyi' }}</span>
                        </button>
                        <x-input-error :messages="$errors->get('micEnabled')" class="mt-2" />
                    </div>

                    <div class="border-t border-gray-100 dark:border-gray-700 pt-4">
                        <x-input-label value="Pin Kursi" />
                        <button type="button" wire:click="toggleModalPinned"
                            class="mt-1 inline-flex items-center gap-2 rounded-full pl-1 pr-3 py-1 transition {{ $isPinned ? 'bg-amber-500' : 'bg-gray-300 dark:bg-gray-600' }}">
                            <span class="relative inline-flex h-6 w-11 items-center rounded-full bg-black/20">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition {{ $isPinned ? 'translate-x-6' : 'translate-x-1' }}"></span>
                            </span>
                            <span class="text-sm font-medium text-white">{{ $isPinned ? 'Di-pin 📌' : 'Tidak di-pin' }}</span>
                        </button>
                        <p class="text-xs text-gray-400 mt-1">
                            Kalau di-pin, nama/foto/coin kursi ini TIDAK ikut ke-reset oleh Reset Leaderboard/Reset Coin, dan tidak akan ditimpa gifter baru dari auto-gift. Cocok utk kursi sponsor/tamu tetap.
                        </p>
                        <x-input-error :messages="$errors->get('isPinned')" class="mt-2" />
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="closeEdit"
                            class="px-4 py-2 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-md hover:bg-gray-50 dark:hover:bg-gray-800">
                            Batal
                        </button>
                        <x-primary-button>
                            Simpan
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
