<div>
    <x-slot name="header">
        @include('livewire.project-live.partials.nav', ['projectLive' => $projectLive, 'title' => 'Pemetaan Gift'])
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 space-y-1">
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    Petakan satu gift ke satu atau lebih gift lain di katalog — ikon yang tampil di Live diambil dari
                    ikon salah satu gift tujuannya (dipilih ACAK kalau lebih dari satu, mis. gift
                    <strong>Donat</strong> dipetakan ke <strong>Lion</strong> &amp; <strong>Panda</strong>, jadi
                    begitu ada yang kirim Donat, ikon Lion ATAU Panda yang muncul sebentar di pojok kotaknya).
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
                            <div class="flex -space-x-1.5 flex-shrink-0">
                                @foreach ($gift->mappedTargets as $target)
                                    @if ($target->icon_url)
                                        <img src="{{ $target->icon_url }}" title="{{ $target->name }}" class="w-8 h-8 rounded-full ring-2 ring-white dark:ring-gray-800" alt="">
                                    @else
                                        <div title="{{ $target->name }}" class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 ring-2 ring-white dark:ring-gray-800"></div>
                                    @endif
                                @endforeach
                            </div>
                            <p class="text-sm text-gray-800 dark:text-gray-200 truncate">{{ $gift->mappedTargets->pluck('name')->join(', ') }}</p>
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

            <!-- Animasi Overlay per Gift -->
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 space-y-1 mt-6">
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    Petakan gift ASLI ke satu atau lebih <strong>animasi overlay</strong> — begitu gift itu benar-benar
                    diterima, salah satu animasinya (dipilih ACAK kalau lebih dari satu) diputar di halaman
                    "Show Animasi Overlay".
                </p>
                <p class="text-xs text-gray-400">
                    Katalog animasinya dikelola di menu "Animasi Overlay" (atas). Berlaku bareng semua project.
                </p>
            </div>

            <div class="flex justify-end">
                <button type="button" wire:click="openOverlayCreate"
                    class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded-md hover:bg-indigo-700">
                    + Animasi Overlay Gift
                </button>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($overlayMappings as $gift)
                    <div wire:key="overlay-mapping-row-{{ $gift->id }}" class="flex items-center gap-3 p-3">
                        <div class="flex items-center gap-2 flex-1 min-w-0">
                            @if ($gift->icon_url)
                                <img src="{{ $gift->icon_url }}" class="w-8 h-8 rounded flex-shrink-0" alt="">
                            @else
                                <div class="w-8 h-8 rounded bg-gray-200 dark:bg-gray-700 flex-shrink-0"></div>
                            @endif
                            <div class="min-w-0">
                                <p class="text-sm text-gray-800 dark:text-gray-200 truncate">{{ $gift->name }}</p>
                                <p class="text-xs text-indigo-500 dark:text-indigo-400 truncate">
                                    🎬 {{ $gift->overlayAnimations->pluck('name')->join(', ') }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 flex-shrink-0">
                            <button type="button" wire:click="openOverlayEdit({{ $gift->id }})"
                                class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                                Edit
                            </button>
                            <button type="button" wire:click="removeOverlayMapping({{ $gift->id }})" wire:confirm="Hapus animasi overlay gift &quot;{{ $gift->name }}&quot;?"
                                class="text-xs font-semibold text-red-500 hover:underline">
                                Hapus
                            </button>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-gray-400 px-3 py-6 text-center">Belum ada gift yang dipetakan ke animasi overlay.</p>
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

                <!-- Pilih gift tujuan (ikonnya yang dipakai, bisa lebih dari 1) -->
                <div class="relative" wire:key="target-picker">
                    <x-input-label value="Dipetakan ke ikon gift (bisa lebih dari 1, nanti dipilih acak)" />

                    @if (! empty($targetGiftIds))
                        <div class="flex flex-wrap gap-1.5 mt-1.5 mb-1.5">
                            @foreach ($targetGiftIds as $pickedId)
                                @php $picked = $pickedTargets->get($pickedId); @endphp
                                @if ($picked)
                                    <span wire:key="picked-target-{{ $picked->id }}" class="inline-flex items-center gap-1.5 pl-1 pr-2 py-1 rounded-full bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-200 dark:border-indigo-700">
                                        @if ($picked->icon_url)
                                            <img src="{{ $picked->icon_url }}" class="w-5 h-5 rounded-full" alt="">
                                        @endif
                                        <span class="text-xs font-medium text-indigo-700 dark:text-indigo-300">{{ $picked->name }}</span>
                                        <button type="button" wire:click="removeTarget({{ $picked->id }})" class="text-indigo-400 hover:text-red-500 text-xs leading-none">&times;</button>
                                    </span>
                                @endif
                            @endforeach
                        </div>
                    @endif

                    <x-text-input wire:model.live.debounce.300ms="targetSearch" type="text" placeholder="Cari nama gift buat ditambah..." class="block w-full text-sm" />

                    @if ($targetResults->isNotEmpty())
                        <div class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg max-h-48 overflow-y-auto">
                            @foreach ($targetResults as $result)
                                <button type="button" wire:click="addTarget({{ $result->id }})"
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

    <!-- Modal Create/Edit Animasi Overlay Gift -->
    <div x-show="$wire.showOverlayModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div x-show="$wire.showOverlayModal" x-transition.opacity wire:click="closeOverlayModal" class="fixed inset-0 bg-black/60"></div>

            <div x-show="$wire.showOverlayModal" x-transition
                class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6 space-y-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    Animasi Overlay Gift
                </h3>

                @error('overlayForm')
                    <p class="text-sm text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-md p-2">{{ $message }}</p>
                @enderror

                <div class="relative" wire:key="overlay-gift-picker">
                    <x-input-label value="Gift" />
                    <div class="flex items-center gap-2 mt-1">
                        <x-text-input wire:model.live.debounce.300ms="overlayGiftSearch" type="text" placeholder="Cari nama gift..." class="block w-full text-sm" />
                        @if ($overlayGiftId)
                            <button type="button" wire:click="clearOverlayGiftPick" class="flex-shrink-0 text-xs text-gray-400 hover:text-red-500">Ganti</button>
                        @endif
                    </div>
                    @if ($overlayGiftResults->isNotEmpty())
                        <div class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg max-h-48 overflow-y-auto">
                            @foreach ($overlayGiftResults as $result)
                                <button type="button" wire:click="pickOverlayGift({{ $result->id }})"
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

                <div wire:key="overlay-gift-animation-picker">
                    <x-input-label value="Animasi Overlay (bisa lebih dari 1, nanti dipilih acak)" />
                    @if ($overlayAnimations->isEmpty())
                        <p class="text-xs text-gray-400 mt-1">
                            Belum ada animasi di katalog. Tambah dulu lewat menu "Animasi Overlay" di atas.
                        </p>
                    @else
                        <div class="grid grid-cols-2 gap-1.5 mt-1.5 max-h-40 overflow-y-auto p-1">
                            @foreach ($overlayAnimations as $animation)
                                <label wire:key="overlay-gift-opt-{{ $animation->id }}" class="flex items-center gap-1.5 px-2 py-1.5 rounded-md border cursor-pointer transition {{ in_array($animation->id, $overlayAnimationIds, true) ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30' : 'border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                                    <input type="checkbox" wire:click="toggleOverlayAnimation({{ $animation->id }})" @checked(in_array($animation->id, $overlayAnimationIds, true)) class="rounded border-gray-300 dark:border-gray-600 flex-shrink-0">
                                    <span class="text-xs text-gray-700 dark:text-gray-300 truncate">{{ $animation->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" wire:click="closeOverlayModal"
                        class="px-4 py-2 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-md hover:bg-gray-50 dark:hover:bg-gray-800">
                        Batal
                    </button>
                    <button type="button" wire:click="saveOverlay"
                        class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                        Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
