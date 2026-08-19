<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Pemetaan Gift
            </h2>
            <a href="{{ route('project-live.admin', $projectLive) }}" wire:navigate
                class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">&larr; Kembali ke Admin</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 space-y-1">
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    Petakan satu gift ke gift lain di katalog — ikon yang tampil di Live diambil dari ikon gift
                    tujuannya (mis. gift <strong>Donat</strong> dipetakan ke gift <strong>Lion</strong>, jadi begitu
                    ada yang kirim Donat, ikon Lion yang muncul sebentar di pojok kotaknya).
                </p>
                <p class="text-xs text-gray-400">
                    Satu gift tujuan boleh dipakai untuk banyak pemetaan sekaligus. Katalog ini dipakai bareng semua project.
                </p>
            </div>

            <div class="flex justify-end">
                <button type="button" wire:click="openCreate"
                    class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded-md hover:bg-indigo-700">
                    + Tambah Pemetaan
                </button>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($mappings as $gift)
                    <div wire:key="mapping-row-{{ $gift->id }}" class="flex items-center gap-3 p-3">
                        <div class="flex items-center gap-2 flex-1 min-w-0">
                            @if ($gift->icon_url)
                                <img src="{{ $gift->icon_url }}" class="w-8 h-8 rounded flex-shrink-0" alt="">
                            @else
                                <div class="w-8 h-8 rounded bg-gray-200 dark:bg-gray-700 flex-shrink-0"></div>
                            @endif
                            <p class="text-sm text-gray-800 dark:text-gray-200 truncate">{{ $gift->name }}</p>
                        </div>

                        <span class="text-gray-300 dark:text-gray-600 flex-shrink-0">&rarr;</span>

                        <div class="flex items-center gap-2 flex-1 min-w-0">
                            @if ($gift->mappedTo?->icon_url)
                                <img src="{{ $gift->mappedTo->icon_url }}" class="w-8 h-8 rounded flex-shrink-0" alt="">
                            @else
                                <div class="w-8 h-8 rounded bg-gray-200 dark:bg-gray-700 flex-shrink-0"></div>
                            @endif
                            <p class="text-sm text-gray-800 dark:text-gray-200 truncate">{{ $gift->mappedTo?->name }}</p>
                        </div>

                        <div class="flex items-center gap-2 flex-shrink-0">
                            <button type="button" wire:click="openEdit({{ $gift->id }})"
                                class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                                Edit
                            </button>
                            <button type="button" wire:click="delete({{ $gift->id }})" wire:confirm="Hapus pemetaan gift &quot;{{ $gift->name }}&quot;?"
                                class="text-xs font-semibold text-red-500 hover:underline">
                                Hapus
                            </button>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-gray-400 px-3 py-6 text-center">Belum ada pemetaan gift. Klik "+ Tambah Pemetaan" untuk mulai.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Modal Create/Edit Pemetaan -->
    <div x-show="$wire.showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div x-show="$wire.showModal" x-transition.opacity wire:click="closeModal" class="fixed inset-0 bg-black/60"></div>

            <div x-show="$wire.showModal" x-transition
                class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6 space-y-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    {{ $editingId ? 'Edit Pemetaan' : 'Tambah Pemetaan' }}
                </h3>

                @error('form')
                    <p class="text-sm text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-md p-2">{{ $message }}</p>
                @enderror

                <!-- Pilih gift sumber -->
                <div class="relative" wire:key="source-picker">
                    <x-input-label value="Gift yang dikirim" />
                    <div class="flex items-center gap-2 mt-1">
                        <x-text-input wire:model.live.debounce.300ms="sourceSearch" type="text" placeholder="Cari nama gift..." class="block w-full text-sm" />
                        @if ($sourceGiftId)
                            <button type="button" wire:click="clearSourcePick" class="flex-shrink-0 text-xs text-gray-400 hover:text-red-500">Ganti</button>
                        @endif
                    </div>
                    @if ($sourceResults->isNotEmpty())
                        <div class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg max-h-48 overflow-y-auto">
                            @foreach ($sourceResults as $result)
                                <button type="button" wire:click="pickSource({{ $result->id }})"
                                    class="w-full flex items-center gap-2 px-3 py-2 text-left hover:bg-gray-50 dark:hover:bg-gray-800">
                                    @if ($result->icon_url)
                                        <img src="{{ $result->icon_url }}" class="w-6 h-6 rounded flex-shrink-0" alt="">
                                    @endif
                                    <span class="text-sm text-gray-700 dark:text-gray-200 truncate">{{ $result->name }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="text-center text-gray-300 dark:text-gray-600">&darr;</div>

                <!-- Pilih gift tujuan (ikonnya yang dipakai) -->
                <div class="relative" wire:key="target-picker">
                    <x-input-label value="Dipetakan ke ikon gift" />
                    <div class="flex items-center gap-2 mt-1">
                        <x-text-input wire:model.live.debounce.300ms="targetSearch" type="text" placeholder="Cari nama gift..." class="block w-full text-sm" />
                        @if ($targetGiftId)
                            <button type="button" wire:click="clearTargetPick" class="flex-shrink-0 text-xs text-gray-400 hover:text-red-500">Ganti</button>
                        @endif
                    </div>
                    @if ($targetResults->isNotEmpty())
                        <div class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg max-h-48 overflow-y-auto">
                            @foreach ($targetResults as $result)
                                <button type="button" wire:click="pickTarget({{ $result->id }})"
                                    class="w-full flex items-center gap-2 px-3 py-2 text-left hover:bg-gray-50 dark:hover:bg-gray-800">
                                    @if ($result->icon_url)
                                        <img src="{{ $result->icon_url }}" class="w-6 h-6 rounded flex-shrink-0" alt="">
                                    @endif
                                    <span class="text-sm text-gray-700 dark:text-gray-200 truncate">{{ $result->name }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

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
