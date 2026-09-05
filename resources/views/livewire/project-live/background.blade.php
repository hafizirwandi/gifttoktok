<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Background
            </h2>
            <a href="{{ route('project-live.admin', $projectLive) }}" wire:navigate
                class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">&larr; Kembali ke Admin</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 flex items-start justify-between gap-3">
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    Pasang gambar/video custom sebagai background LAYAR PENUH (di belakang semua kotak kursi,
                    cuma 1 boleh aktif sekaligus), atau taruh di KOTAK KURSI nomor tertentu — kotak itu otomatis
                    berhenti ikut sistem auto-gift selama background-nya aktif.
                </p>
                <button wire:click="openCreate" type="button"
                    class="flex-shrink-0 inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded-md hover:bg-indigo-700">
                    + Tambah Background
                </button>
            </div>

            @php
                $screenBgs = $backgrounds->where('placement', 'screen');
                $seatBgs = $backgrounds->where('placement', 'seat');
            @endphp

            <div class="space-y-3">
                <h3 class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Layar Penuh</h3>

                @forelse ($screenBgs as $bg)
                    @include('livewire.project-live.partials.background-row', ['bg' => $bg])
                @empty
                    <p class="text-sm text-gray-400 italic">Belum ada background layar.</p>
                @endforelse
            </div>

            <div class="space-y-3">
                <h3 class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Kotak Kursi</h3>

                @forelse ($seatBgs as $bg)
                    @include('livewire.project-live.partials.background-row', ['bg' => $bg])
                @empty
                    <p class="text-sm text-gray-400 italic">Belum ada background per-kotak.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Modal Tambah/Edit Background -->
    <div x-show="$wire.showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div x-show="$wire.showModal" x-transition.opacity wire:click="closeModal"
                class="fixed inset-0 bg-black/60"></div>

            <div x-show="$wire.showModal" x-transition
                class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6 space-y-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    {{ $editingId ? 'Edit Background' : 'Tambah Background' }}
                </h3>

                <form wire:submit="save" class="space-y-4">
                    <div>
                        <x-input-label for="name" value="Nama (opsional, buat penanda)" />
                        <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text" placeholder="mis. Wallpaper konser" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label value="Tipe" />
                        <div class="flex gap-2 mt-1">
                            @foreach (\App\Enums\BackgroundType::cases() as $option)
                                <button type="button" wire:click="$set('type', '{{ $option->value }}')"
                                    class="flex-1 px-3 py-1.5 text-sm rounded-md border transition {{ $type === $option->value ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300' : 'border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                                    {{ $option->label() }}
                                </button>
                            @endforeach
                        </div>
                        <x-input-error :messages="$errors->get('type')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="file" :value="$type === 'video' ? 'File Video' : 'File Gambar'" />
                        <input type="file" wire:model="file" id="file"
                            accept="{{ $type === 'video' ? 'video/mp4,video/webm' : 'image/*' }}"
                            class="block mt-1 w-full text-sm text-gray-600 dark:text-gray-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 dark:file:bg-indigo-900/40 file:text-indigo-700 dark:file:text-indigo-300">
                        <p class="text-xs text-gray-400 mt-1">
                            @if ($type === 'video')
                                MP4 atau WEBM, maksimal 50MB.
                            @else
                                JPG, PNG, atau WEBP, maksimal 8MB.
                            @endif
                            @if ($editingId)
                                Kosongkan kalau tidak mau ganti file.
                            @endif
                        </p>
                        <div wire:loading wire:target="file" class="text-xs text-gray-400 mt-1">Mengunggah...</div>
                        <x-input-error :messages="$errors->get('file')" class="mt-2" />
                    </div>

                    @if ($type === 'video')
                        <div>
                            <x-input-label value="Suara Video" />
                            <button type="button" wire:click="toggleAudioEnabled"
                                class="mt-1 inline-flex items-center gap-2 rounded-full pl-1 pr-3 py-1 transition {{ $audioEnabled ? 'bg-green-600' : 'bg-gray-300 dark:bg-gray-600' }}">
                                <span class="relative inline-flex h-6 w-11 items-center rounded-full bg-black/20">
                                    <span class="inline-block h-4 w-4 transform rounded-full bg-white transition {{ $audioEnabled ? 'translate-x-6' : 'translate-x-1' }}"></span>
                                </span>
                                <span class="text-sm font-medium text-white">{{ $audioEnabled ? 'Nyala' : 'Senyap' }}</span>
                            </button>
                            <p class="text-xs text-gray-400 mt-1">
                                Kalau dinyalakan, suara video ini CUMA terdengar di halaman Live asli (browser source OBS) - Preview Live selalu senyap apa pun pengaturan ini.
                            </p>
                            <x-input-error :messages="$errors->get('audioEnabled')" class="mt-2" />
                        </div>
                    @endif

                    <div>
                        <x-input-label value="Penempatan" />
                        <div class="flex gap-2 mt-1">
                            @foreach (\App\Enums\BackgroundPlacement::cases() as $option)
                                <button type="button" wire:click="$set('placement', '{{ $option->value }}')"
                                    class="flex-1 px-3 py-1.5 text-sm rounded-md border transition {{ $placement === $option->value ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300' : 'border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                                    {{ $option->label() }}
                                </button>
                            @endforeach
                        </div>
                        <x-input-error :messages="$errors->get('placement')" class="mt-2" />
                    </div>

                    @if ($placement === 'seat')
                        <div>
                            <x-input-label for="seatPosition" value="Nomor Kotak" />
                            <select wire:model="seatPosition" id="seatPosition"
                                class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm text-sm">
                                <option value="">Pilih nomor kotak...</option>
                                @for ($i = 1; $i <= $seatCount; $i++)
                                    <option value="{{ $i }}">Kotak #{{ $i }}</option>
                                @endfor
                            </select>
                            <x-input-error :messages="$errors->get('seatPosition')" class="mt-2" />
                        </div>
                    @endif

                    <div class="border-t border-gray-100 dark:border-gray-700 pt-4 space-y-3">
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400">Ukuran & Posisi</p>

                        <div>
                            <x-input-label value="Fit" />
                            <select wire:model="fitMode"
                                class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm text-sm">
                                @foreach (\App\Enums\BackgroundFit::cases() as $option)
                                    <option value="{{ $option->value }}">{{ $option->label() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <x-input-label for="offsetX" value="Geser X (px)" />
                                <x-text-input wire:model="offsetX" id="offsetX" class="block mt-1 w-full" type="number" />
                                <x-input-error :messages="$errors->get('offsetX')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="offsetY" value="Geser Y (px)" />
                                <x-text-input wire:model="offsetY" id="offsetY" class="block mt-1 w-full" type="number" />
                                <x-input-error :messages="$errors->get('offsetY')" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="scale" value="Skala (%)" />
                            <x-text-input wire:model="scale" id="scale" class="block mt-1 w-full" type="number" min="10" max="300" />
                            <x-input-error :messages="$errors->get('scale')" class="mt-2" />
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="closeModal"
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
