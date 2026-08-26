<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Frame Host
            </h2>
            <a href="{{ route('project-live.admin', $projectLive) }}" wire:navigate
                class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">&larr; Kembali ke Admin</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    Border dekoratif buat ditaruh di sekitar kamera host — dipasang sebagai OBS Browser Source
                    <strong>terpisah</strong> dari halaman Live (jadi bisa diposisikan bebas di scene OBS kamu).
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-4">
                    <!-- Orientasi -->
                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 space-y-3">
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Orientasi</p>
                        <div class="grid grid-cols-1 gap-2">
                            @foreach (\App\Enums\FrameOrientation::cases() as $orientation)
                                <button type="button" wire:click="updateOrientation('{{ $orientation->value }}')"
                                    class="text-left px-3 py-2 rounded-md border text-xs font-medium transition {{ $projectLive->frame_orientation === $orientation ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300' : 'border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                                    {{ $orientation->label() }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Tampilan -->
                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 space-y-4">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Border</p>
                            <button type="button" wire:click="toggleVisible"
                                class="flex-shrink-0 inline-flex items-center gap-1.5 rounded-full pl-1 pr-3 py-1 transition {{ $projectLive->frame_visible ? 'bg-green-600' : 'bg-gray-300 dark:bg-gray-600' }}">
                                <span class="relative inline-flex h-5 w-9 items-center rounded-full bg-black/20">
                                    <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white transition {{ $projectLive->frame_visible ? 'translate-x-[18px]' : 'translate-x-1' }}"></span>
                                </span>
                                <span class="text-sm font-semibold text-white">{{ $projectLive->frame_visible ? 'Show' : 'Hide' }}</span>
                            </button>
                        </div>

                        <div>
                            <x-input-label for="color" value="Warna" />
                            <div class="flex items-center gap-2 mt-1">
                                <input type="color" wire:model.live="color" id="color" class="w-10 h-9 rounded border border-gray-300 dark:border-gray-600 bg-transparent p-0.5">
                                <x-text-input wire:model.live.debounce.300ms="color" type="text" class="block w-full text-sm font-mono" maxlength="7" placeholder="#38bdf8" />
                            </div>
                            <x-input-error :messages="$errors->get('color')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="radius" value="Radius sudut (px)" />
                            <x-text-input wire:model.live.debounce.300ms="radius" type="number" id="radius" min="0" max="200" class="block mt-1 w-full text-sm" />
                            <x-input-error :messages="$errors->get('radius')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="borderWidth" value="Tebal border (px)" />
                            <x-text-input wire:model.live.debounce.300ms="borderWidth" type="number" id="borderWidth" min="1" max="100" class="block mt-1 w-full text-sm" />
                            <x-input-error :messages="$errors->get('borderWidth')" class="mt-1" />
                        </div>

                        <!-- Custom width/height: tombol Orientasi di atas cuma ISI dua angka
                             ini ke preset bawaannya (lihat FrameOrientation::ratioW()/ratioH())
                             - rasio SEBENARNYA yg dipakai render selalu dari sini, jadi admin
                             bebas ubah manual ke rasio apa pun. -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <x-input-label for="ratioW" value="Lebar (rasio)" />
                                <x-text-input wire:model.live.debounce.300ms="ratioW" type="number" id="ratioW" min="1" max="100" class="block mt-1 w-full text-sm" />
                                <x-input-error :messages="$errors->get('ratioW')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="ratioH" value="Tinggi (rasio)" />
                                <x-text-input wire:model.live.debounce.300ms="ratioH" type="number" id="ratioH" min="1" max="100" class="block mt-1 w-full text-sm" />
                                <x-input-error :messages="$errors->get('ratioH')" class="mt-1" />
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="button" wire:click="saveAppearance"
                                class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded-md hover:bg-indigo-700">
                                Simpan
                            </button>
                        </div>
                    </div>

                    <!-- Efek Pulse (kedap-kedip) -->
                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 space-y-4">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Efek Pulse</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Border berkedip gonta-ganti warna, bukan warna diam.</p>
                            </div>
                            <button type="button" wire:click="togglePulse"
                                class="flex-shrink-0 inline-flex items-center gap-1.5 rounded-full pl-1 pr-3 py-1 transition {{ $projectLive->frame_pulse ? 'bg-green-600' : 'bg-gray-300 dark:bg-gray-600' }}">
                                <span class="relative inline-flex h-5 w-9 items-center rounded-full bg-black/20">
                                    <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white transition {{ $projectLive->frame_pulse ? 'translate-x-[18px]' : 'translate-x-1' }}"></span>
                                </span>
                                <span class="text-sm font-semibold text-white">{{ $projectLive->frame_pulse ? 'ON' : 'OFF' }}</span>
                            </button>
                        </div>

                        <div>
                            <x-input-label for="pulseSpeedMs" value="Kecepatan (ms per siklus)" />
                            <x-text-input wire:model.live.debounce.300ms="pulseSpeedMs" type="number" id="pulseSpeedMs" min="200" max="10000" step="100" class="block mt-1 w-full text-sm" />
                            <p class="text-xs text-gray-400 mt-1">Makin kecil makin cepat kedap-kedipnya. Mis. 1500 = 1,5 detik per siklus.</p>
                            <x-input-error :messages="$errors->get('pulseSpeedMs')" class="mt-1" />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <x-input-label for="pulseColor1" value="Warna 1" />
                                <div class="flex items-center gap-1 mt-1">
                                    <input type="color" wire:model.live="pulseColor1" id="pulseColor1" class="w-9 h-9 rounded border border-gray-300 dark:border-gray-600 bg-transparent p-0.5 flex-shrink-0">
                                    <x-text-input wire:model.live.debounce.300ms="pulseColor1" type="text" class="block w-full text-xs font-mono" maxlength="7" />
                                </div>
                                <x-input-error :messages="$errors->get('pulseColor1')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="pulseColor2" value="Warna 2" />
                                <div class="flex items-center gap-1 mt-1">
                                    <input type="color" wire:model.live="pulseColor2" id="pulseColor2" class="w-9 h-9 rounded border border-gray-300 dark:border-gray-600 bg-transparent p-0.5 flex-shrink-0">
                                    <x-text-input wire:model.live.debounce.300ms="pulseColor2" type="text" class="block w-full text-xs font-mono" maxlength="7" />
                                </div>
                                <x-input-error :messages="$errors->get('pulseColor2')" class="mt-1" />
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between">
                                <x-input-label for="pulseColor3" value="Warna 3 (opsional)" />
                                @if ($pulseColor3 !== '')
                                    <button type="button" wire:click="clearPulseColor3" class="text-xs font-semibold text-red-500 hover:underline">Hapus, cuma 2 warna</button>
                                @endif
                            </div>
                            <div class="flex items-center gap-1 mt-1">
                                <input type="color" wire:model.live="pulseColor3" id="pulseColor3" class="w-9 h-9 rounded border border-gray-300 dark:border-gray-600 bg-transparent p-0.5 flex-shrink-0" value="{{ $pulseColor3 ?: '#000000' }}">
                                <x-text-input wire:model.live.debounce.300ms="pulseColor3" type="text" class="block w-full text-xs font-mono" maxlength="7" placeholder="Kosongkan buat 2 warna saja" />
                            </div>
                            <x-input-error :messages="$errors->get('pulseColor3')" class="mt-1" />
                        </div>

                        <div class="flex justify-end">
                            <button type="button" wire:click="saveAppearance"
                                class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded-md hover:bg-indigo-700">
                                Simpan
                            </button>
                        </div>
                    </div>

                    <!-- Efek Pulse Kursi: sama persis warna & kecepatannya dgn Efek Pulse
                         border frame di atas (pulseColor1/2/3, pulseSpeedMs) - admin
                         cukup centang kotak KURSI mana saja (bisa lebih dari 1 sekaligus)
                         yang mau ikut berkedip di halaman Live, lihat
                         App\Livewire\ProjectLive\FrameHost::updatedSeatPulsePositions(). -->
                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 space-y-4">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Efek Pulse Kursi</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Kotak kursi yang dicentang ikut berkedip di halaman Live, pakai warna &amp; kecepatan Efek Pulse di atas.</p>
                            </div>
                            <button type="button" wire:click="toggleSeatPulse"
                                class="flex-shrink-0 inline-flex items-center gap-1.5 rounded-full pl-1 pr-3 py-1 transition {{ $projectLive->seat_pulse_enabled ? 'bg-green-600' : 'bg-gray-300 dark:bg-gray-600' }}">
                                <span class="relative inline-flex h-5 w-9 items-center rounded-full bg-black/20">
                                    <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white transition {{ $projectLive->seat_pulse_enabled ? 'translate-x-[18px]' : 'translate-x-1' }}"></span>
                                </span>
                                <span class="text-sm font-semibold text-white">{{ $projectLive->seat_pulse_enabled ? 'ON' : 'OFF' }}</span>
                            </button>
                        </div>

                        <div class="grid grid-cols-4 gap-2">
                            @for ($i = 1; $i <= $projectLive->display_mode->seatCount(); $i++)
                                <label class="inline-flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-300">
                                    <input type="checkbox" wire:model.live="seatPulsePositions" value="{{ $i }}"
                                        class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                                    Kursi #{{ $i }}
                                </label>
                            @endfor
                        </div>
                        <x-input-error :messages="$errors->get('seatPulsePositions')" class="mt-1" />
                        <p class="text-xs text-gray-400">Langsung tersimpan begitu dicentang/di-uncheck.</p>
                    </div>

                    <!-- URL OBS -->
                    <div class="bg-gray-50 dark:bg-gray-900/50 rounded-md p-3 space-y-2 text-xs" x-data="{ copied: false }">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-gray-500 dark:text-gray-400">URL buat OBS Browser Source:</p>
                            <button type="button"
                                x-on:click="navigator.clipboard.writeText($refs.frameUrl.innerText); copied = true; setTimeout(() => copied = false, 1500)"
                                class="flex-shrink-0 inline-flex items-center px-2 py-1 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 text-[10px] font-semibold rounded hover:bg-indigo-100 dark:hover:bg-indigo-900/50">
                                <span x-text="copied ? 'Tersalin!' : 'Copy'"></span>
                            </button>
                        </div>
                        <p x-ref="frameUrl" class="font-mono text-gray-700 dark:text-gray-300 break-all select-all">{{ route('project-live.frame-host-live', $projectLive) }}</p>
                    </div>
                </div>

                <!-- Preview -->
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
                    <div class="flex items-center justify-between gap-3 mb-3">
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Preview</p>
                        <a href="{{ route('project-live.frame-host-live', $projectLive) }}" target="_blank" rel="noopener"
                            class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded-md hover:bg-indigo-700">
                            Lihat Frame &nearr;
                        </a>
                    </div>
                    @php
                        $previewBorderStyle = $projectLive->frame_visible
                            ? ($projectLive->frame_pulse
                                ? 'border: '.$borderWidth.'px solid '.$pulseColor1.'; animation: gtt-frame-pulse-preview '.$pulseSpeedMs.'ms ease-in-out infinite;'
                                : 'border: '.$borderWidth.'px solid '.$color.';')
                            : 'border: 2px dashed #9ca3af;';
                    @endphp

                    @if ($projectLive->frame_visible && $projectLive->frame_pulse)
                        <style>
                            @keyframes gtt-frame-pulse-preview {
                                0% { border-color: {{ $pulseColor1 }}; }
                                @if ($pulseColor3 !== '')
                                    33% { border-color: {{ $pulseColor2 }}; }
                                    66% { border-color: {{ $pulseColor3 }}; }
                                @else
                                    50% { border-color: {{ $pulseColor2 }}; }
                                @endif
                                100% { border-color: {{ $pulseColor1 }}; }
                            }
                        </style>
                    @endif

                    <!-- Kotak preview: rasio SELALU dari $ratioW/$ratioH (bukan cabang per
                         orientasi lagi) - formula "contain" yg sama dgn DisplayMode (lihat
                         App\Enums\DisplayMode), biar otomatis pas apa pun rasionya (potret/
                         lanskap/persegi/custom) di dalam kotak preview 300x280. -->
                    <div class="bg-black rounded-lg p-6 flex items-center justify-center min-h-[320px]">
                        <div style="
                            width: min(300px, 280px * {{ $ratioW }} / {{ $ratioH }});
                            aspect-ratio: {{ $ratioW }} / {{ $ratioH }};
                            border-radius: {{ $radius }}px;
                            {{ $previewBorderStyle }}
                        "></div>
                    </div>
                    @unless ($projectLive->frame_visible)
                        <p class="text-xs text-amber-600 dark:text-amber-400 mt-2">Border sedang di-hide (garis putus-putus cuma penanda area, tidak ikut tampil di OBS).</p>
                    @endunless
                </div>
            </div>
        </div>
    </div>
</div>
