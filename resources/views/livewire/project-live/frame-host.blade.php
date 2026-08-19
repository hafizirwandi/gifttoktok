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

                        <div class="flex justify-end">
                            <button type="button" wire:click="saveAppearance"
                                class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded-md hover:bg-indigo-700">
                                Simpan
                            </button>
                        </div>
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
                    <div class="bg-black rounded-lg p-6 flex items-center justify-center min-h-[320px]">
                        @if ($projectLive->frame_orientation->value === 'landscape')
                            <div class="w-full max-w-[360px] aspect-[16/9]"
                                style="border-radius: {{ $radius }}px; {{ $projectLive->frame_visible ? 'border: '.$borderWidth.'px solid '.$color.';' : 'border: 2px dashed #9ca3af;' }}">
                            </div>
                        @else
                            <div class="h-[320px] aspect-[9/16]"
                                style="border-radius: {{ $radius }}px; {{ $projectLive->frame_visible ? 'border: '.$borderWidth.'px solid '.$color.';' : 'border: 2px dashed #9ca3af;' }}">
                            </div>
                        @endif
                    </div>
                    @unless ($projectLive->frame_visible)
                        <p class="text-xs text-amber-600 dark:text-amber-400 mt-2">Border sedang di-hide (garis putus-putus cuma penanda area, tidak ikut tampil di OBS).</p>
                    @endunless
                </div>
            </div>
        </div>
    </div>
</div>
