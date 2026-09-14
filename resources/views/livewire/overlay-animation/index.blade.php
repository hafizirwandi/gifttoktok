<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Animasi Overlay
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 space-y-1">
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    Katalog animasi overlay (video <strong>WebM</strong>) yang dipakai bareng semua project - petakan
                    ke gift asli lewat halaman "Pemetaan Gift", atau ke Event Trigger (join/follow/dst) lewat halaman
                    "Event Trigger" di masing-masing project.
                </p>
                <p class="text-xs text-gray-400">
                    "Durasi Tayang" dipakai halaman "Show Animasi Overlay" buat tahu kapan harus lanjut ke antrian
                    berikutnya — Otomatis (sampai video-nya sendiri selesai) atau Manual (potong di detik tertentu).
                </p>
            </div>

            <div class="flex justify-end">
                <button type="button" wire:click="openCreate"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500">
                    + Animasi Baru
                </button>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($animations as $animation)
                    <div wire:key="overlay-row-{{ $animation->id }}" class="flex items-center gap-3 p-3">
                        <div class="w-12 h-12 rounded bg-gray-100 dark:bg-gray-900 flex-shrink-0 overflow-hidden flex items-center justify-center">
                            @if ($animation->fileUrl())
                                <video src="{{ $animation->fileUrl() }}" class="max-w-full max-h-full object-contain" muted loop autoplay playsinline></video>
                            @endif
                        </div>

                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-800 dark:text-gray-200 truncate">{{ $animation->name }}</p>
                            <p class="text-xs text-gray-400">
                                @if ($animation->duration_mode === 'auto')
                                    Otomatis (sampai video selesai)
                                @else
                                    Manual &middot; {{ number_format($animation->duration_ms / 1000, 1) }} detik
                                @endif
                            </p>
                        </div>

                        <button type="button" wire:click="toggleActive({{ $animation->id }})"
                            class="flex-shrink-0 relative inline-flex h-5 w-9 items-center rounded-full transition {{ $animation->active ? 'bg-green-600' : 'bg-gray-300 dark:bg-gray-600' }}">
                            <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white transition {{ $animation->active ? 'translate-x-[18px]' : 'translate-x-1' }}"></span>
                        </button>

                        <div class="flex items-center gap-2 flex-shrink-0">
                            <button type="button" wire:click="openEdit({{ $animation->id }})"
                                class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                                Edit
                            </button>
                            <button type="button" wire:click="delete({{ $animation->id }})" wire:confirm="Hapus animasi &quot;{{ $animation->name }}&quot;? Trigger/gift yang masih memakainya otomatis kehilangan animasi ini."
                                class="text-xs font-semibold text-red-500 hover:underline">
                                Hapus
                            </button>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-gray-400 px-3 py-6 text-center">Belum ada animasi overlay. Klik "+ Animasi Baru" untuk mulai.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Modal Create/Edit Animasi -->
    <div x-show="$wire.showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div x-show="$wire.showModal" x-transition.opacity wire:click="closeModal" class="fixed inset-0 bg-black/60"></div>

            <div x-show="$wire.showModal" x-transition
                class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6 space-y-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    {{ $editingId ? 'Edit Animasi' : 'Animasi Baru' }}
                </h3>

                <div>
                    <x-input-label value="Nama" />
                    <x-text-input wire:model="name" type="text" placeholder="mis. Confetti Emas" class="block w-full text-sm mt-1" />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>

                <div>
                    <x-input-label value="File WebM Animasi" />
                    @if ($editingId && ($current = \App\Models\OverlayAnimation::find($editingId))?->fileUrl())
                        <video src="{{ $current->fileUrl() }}" class="w-24 h-24 object-contain rounded border border-gray-200 dark:border-gray-600 mt-1 mb-1" muted loop autoplay playsinline></video>
                    @endif
                    <input type="file" wire:model="file" accept="video/webm"
                        class="block w-full text-xs text-gray-600 dark:text-gray-300 file:mr-2 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 dark:file:bg-indigo-900/40 file:text-indigo-700 dark:file:text-indigo-300 mt-1">
                    <div wire:loading wire:target="file" class="text-xs text-gray-400 mt-1">Mengunggah...</div>
                    <x-input-error :messages="$errors->get('file')" class="mt-1" />
                    <p class="text-xs text-gray-400 mt-1">Kosongkan kalau tidak mau ganti file (cuma edit nama/durasi).</p>
                </div>

                <div>
                    <x-input-label value="Durasi Tayang" />
                    <div class="grid grid-cols-2 gap-1.5 mt-1">
                        <label class="flex items-center gap-1.5 px-2.5 py-2 rounded-md border cursor-pointer transition {{ $durationMode === 'auto' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30' : 'border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                            <input type="radio" wire:model.live="durationMode" value="auto" class="border-gray-300 dark:border-gray-600">
                            <span class="text-xs text-gray-700 dark:text-gray-300">Otomatis (sampai selesai)</span>
                        </label>
                        <label class="flex items-center gap-1.5 px-2.5 py-2 rounded-md border cursor-pointer transition {{ $durationMode === 'manual' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30' : 'border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                            <input type="radio" wire:model.live="durationMode" value="manual" class="border-gray-300 dark:border-gray-600">
                            <span class="text-xs text-gray-700 dark:text-gray-300">Manual</span>
                        </label>
                    </div>

                    @if ($durationMode === 'manual')
                        <input type="number" step="0.1" min="0.2" max="60"
                            value="{{ number_format(((float) $durationMs) / 1000, 1) }}"
                            x-on:change="$wire.durationMs = Math.round($event.target.value * 1000)"
                            class="mt-2 block w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200">
                        <p class="text-xs text-gray-400 mt-1">Video dipotong paksa di detik ini, biarpun aslinya lebih panjang/looping.</p>
                    @else
                        <p class="text-xs text-gray-400 mt-2">
                            Video diputar sekali lalu otomatis lanjut ke antrian berikutnya begitu videonya sendiri
                            selesai - tidak perlu isi durasi manual.
                        </p>
                    @endif

                    <x-input-error :messages="$errors->get('durationMode')" class="mt-1" />
                    <x-input-error :messages="$errors->get('durationMs')" class="mt-1" />
                </div>

                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" wire:model="active" class="rounded border-gray-300 dark:border-gray-600">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Aktif</span>
                </label>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" wire:click="closeModal"
                        class="px-4 py-2 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-md hover:bg-gray-50 dark:hover:bg-gray-800">
                        Batal
                    </button>
                    <button type="button" wire:click="save"
                        class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                        Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
