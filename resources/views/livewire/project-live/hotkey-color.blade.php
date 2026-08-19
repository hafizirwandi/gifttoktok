<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Hotkey Warna
            </h2>
            <a href="{{ route('project-live.admin', $projectLive) }}" wire:navigate
                class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">&larr; Kembali ke Admin</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    Pencet hotkey di halaman Live untuk ganti warna + bayangan <strong>semua</strong> kotak kursi
                    yang masih kosong sekaligus (efek global). Kalau tidak ada hotkey warna yang aktif, tiap
                    kotak balik pakai warna kustomnya sendiri-sendiri (atur di bagian "Warna Per Kursi" di bawah).
                </p>
            </div>

            <!-- Hotkey Warna Global -->
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 space-y-3">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Hotkey Warna Global</p>
                    <button type="button" wire:click="openCreate"
                        class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded-md hover:bg-indigo-700">
                        + Tambah
                    </button>
                </div>

                <div class="divide-y divide-gray-100 dark:divide-gray-700 border border-gray-100 dark:border-gray-700 rounded-md">
                    @forelse ($colorHotkeys as $entry)
                        <div wire:key="hotkey-{{ $entry->id }}" class="flex items-center gap-3 p-3">
                            <span class="w-7 h-7 flex-shrink-0 flex items-center justify-center rounded bg-gray-100 dark:bg-gray-900 text-xs font-mono font-bold text-gray-700 dark:text-gray-200 uppercase">
                                {{ $entry->hotkey }}
                            </span>
                            <span class="w-6 h-6 rounded-full flex-shrink-0 border border-gray-200 dark:border-gray-600" style="background: {{ $entry->color }};"></span>
                            <span class="text-xs font-mono text-gray-500 dark:text-gray-400 flex-1">{{ $entry->color }}</span>
                            @if ($projectLive->active_hotkey_color === $entry->color)
                                <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400">Aktif</span>
                            @endif
                            <button type="button" wire:click="openEdit({{ $entry->id }})" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">Edit</button>
                            <button type="button" wire:click="deleteHotkey({{ $entry->id }})" wire:confirm="Hapus hotkey warna '{{ $entry->hotkey }}'?" class="text-xs font-semibold text-red-500 hover:underline">Hapus</button>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400 px-3 py-6 text-center">Belum ada hotkey warna. Klik "+ Tambah" untuk mulai.</p>
                    @endforelse
                </div>

                <div class="border-t border-gray-100 dark:border-gray-700 pt-3 flex items-end gap-2">
                    <div class="flex-1">
                        <x-input-label for="defaultHotkey" value="Hotkey Default (reset)" />
                        <x-text-input wire:model="defaultHotkey" id="defaultHotkey" maxlength="1" class="block mt-1 w-full text-sm" type="text" placeholder="0" />
                        <p class="text-xs text-gray-400 mt-1">Ditekan di Live &rarr; balikin semua kotak kosong ke warna default (bukan warna global lagi).</p>
                        <x-input-error :messages="$errors->get('defaultHotkey')" class="mt-1" />
                    </div>
                    <button type="button" wire:click="saveDefaultHotkey"
                        class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded-md hover:bg-indigo-700">
                        Simpan
                    </button>
                    <button type="button" wire:click="resetActiveColor"
                        class="inline-flex items-center px-3 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-xs font-semibold rounded-md hover:bg-gray-200 dark:hover:bg-gray-600">
                        Reset ke Default Sekarang
                    </button>
                </div>
            </div>

            <!-- Warna Per Kursi -->
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 space-y-3">
                <div>
                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Warna Per Kursi</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Warna default kotak kosong tiap kursi (dipakai kalau tidak ada hotkey warna global yang aktif).</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach ($details as $detail)
                        <div wire:key="seat-color-{{ $detail->id }}" class="flex items-center gap-2 border border-gray-100 dark:border-gray-700 rounded-md p-2">
                            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 w-10 flex-shrink-0">#{{ $detail->position }}</span>
                            <input type="color" wire:model.live="seatColors.{{ $detail->id }}" class="w-9 h-9 rounded border border-gray-300 dark:border-gray-600 bg-transparent p-0.5 flex-shrink-0">
                            <x-text-input wire:model.live.debounce.300ms="seatColors.{{ $detail->id }}" type="text" class="block w-full text-xs font-mono" maxlength="7" />
                            <button type="button" wire:click="saveSeatColor({{ $detail->id }})"
                                class="flex-shrink-0 text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                                Simpan
                            </button>
                        </div>
                        <x-input-error :messages="$errors->get('seatColors.'.$detail->id)" class="-mt-2" />
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Create/Edit Hotkey Warna -->
    <div x-show="$wire.showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div x-show="$wire.showModal" x-transition.opacity wire:click="closeModal" class="fixed inset-0 bg-black/60"></div>

            <div x-show="$wire.showModal" x-transition
                class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-sm w-full p-6 space-y-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    {{ $editingId ? 'Edit Hotkey Warna' : 'Tambah Hotkey Warna' }}
                </h3>

                <div>
                    <x-input-label for="hotkeyInput" value="Hotkey" />
                    <x-text-input wire:model="hotkeyInput" id="hotkeyInput" maxlength="1" class="block mt-1 w-full" type="text" placeholder="w" />
                    <x-input-error :messages="$errors->get('hotkeyInput')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="colorInput" value="Warna" />
                    <div class="flex items-center gap-2 mt-1">
                        <input type="color" wire:model.live="colorInput" id="colorInput" class="w-10 h-9 rounded border border-gray-300 dark:border-gray-600 bg-transparent p-0.5">
                        <x-text-input wire:model.live.debounce.300ms="colorInput" type="text" class="block w-full text-sm font-mono" maxlength="7" />
                    </div>
                    <x-input-error :messages="$errors->get('colorInput')" class="mt-2" />
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" wire:click="closeModal"
                        class="px-4 py-2 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-md hover:bg-gray-50 dark:hover:bg-gray-800">
                        Batal
                    </button>
                    <button type="button" wire:click="saveHotkey"
                        class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                        Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
