<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Event Trigger
            </h2>
            <a href="{{ route('project-live.admin', $projectLive) }}" wire:navigate
                class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">&larr; Kembali ke Admin</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 space-y-1">
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    Selain gift, event lain di TikTok LIVE (join, follow, share, subscribe, like, chat) juga bisa
                    dipetakan ke sebuah gift — begitu event-nya terjadi & trigger-nya aktif, nama penonton itu naik
                    ke papan &amp; ikon gift yang dipilih muncul di kursinya, persis seperti dia benar-benar
                    mengirim gift itu.
                </p>
                <p class="text-xs text-gray-400">
                    Gift asli (fitur utama) selalu aktif secara otomatis, tidak perlu diatur di sini.
                </p>
            </div>

            <div class="flex justify-end">
                <button type="button" wire:click="openCreate"
                    class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded-md hover:bg-indigo-700">
                    + Tambah Trigger
                </button>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($triggers as $trigger)
                    <div wire:key="trigger-row-{{ $trigger->id }}" class="flex items-center gap-3 p-3">
                        <div class="flex items-center gap-2 flex-1 min-w-0">
                            @if ($trigger->mappedGift?->icon_url)
                                <img src="{{ $trigger->mappedGift->icon_url }}" class="w-8 h-8 rounded flex-shrink-0" alt="">
                            @else
                                <div class="w-8 h-8 rounded bg-gray-200 dark:bg-gray-700 flex-shrink-0"></div>
                            @endif
                            <div class="min-w-0">
                                <p class="text-sm text-gray-800 dark:text-gray-200 truncate">{{ $trigger->type->label() }}</p>
                                <p class="text-xs text-gray-400 truncate">
                                    @if ($trigger->type === \App\Enums\EventTriggerType::ChatCommand)
                                        Kata: "{{ $trigger->command_text }}"
                                    @elseif ($trigger->type === \App\Enums\EventTriggerType::Like)
                                        Minimal {{ $trigger->min_count }}x tap
                                    @endif
                                    @if ($trigger->mappedGift)
                                        &rarr; {{ $trigger->mappedGift->name }}
                                    @endif
                                </p>
                            </div>
                        </div>

                        <button type="button" wire:click="toggleActive({{ $trigger->id }})"
                            class="flex-shrink-0 relative inline-flex h-5 w-9 items-center rounded-full transition {{ $trigger->active ? 'bg-green-600' : 'bg-gray-300 dark:bg-gray-600' }}">
                            <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white transition {{ $trigger->active ? 'translate-x-[18px]' : 'translate-x-1' }}"></span>
                        </button>

                        <div class="flex items-center gap-2 flex-shrink-0">
                            <button type="button" wire:click="openEdit({{ $trigger->id }})"
                                class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                                Edit
                            </button>
                            <button type="button" wire:click="delete({{ $trigger->id }})" wire:confirm="Hapus trigger &quot;{{ $trigger->type->label() }}&quot; ini?"
                                class="text-xs font-semibold text-red-500 hover:underline">
                                Hapus
                            </button>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-gray-400 px-3 py-6 text-center">Belum ada event trigger. Klik "+ Tambah Trigger" untuk mulai.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Modal Create/Edit Trigger -->
    <div x-show="$wire.showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div x-show="$wire.showModal" x-transition.opacity wire:click="closeModal" class="fixed inset-0 bg-black/60"></div>

            <div x-show="$wire.showModal" x-transition
                class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6 space-y-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    {{ $editingId ? 'Edit Trigger' : 'Tambah Trigger' }}
                </h3>

                @error('form')
                    <p class="text-sm text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-md p-2">{{ $message }}</p>
                @enderror

                <div>
                    <x-input-label value="Jenis trigger" />
                    <select wire:model.live="type" class="mt-1 block w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200">
                        <option value="">— Pilih —</option>
                        @foreach (\App\Enums\EventTriggerType::cases() as $option)
                            <option value="{{ $option->value }}">{{ $option->label() }}</option>
                        @endforeach
                    </select>
                </div>

                @if ($type !== '')
                    @php $selectedType = \App\Enums\EventTriggerType::from($type); @endphp

                    @if ($selectedType->needsCommandText())
                        <div>
                            <x-input-label value="Kata command di chat" />
                            <x-text-input wire:model="commandText" type="text" placeholder="mis. join" class="block w-full text-sm mt-1" />
                            <p class="text-xs text-gray-400 mt-1">Cocok kalau kata ini muncul di mana saja dalam pesan chat (tidak perlu persis satu kata).</p>
                        </div>
                    @endif

                    @if ($selectedType->needsMinCount())
                        <div>
                            <x-input-label value="Jumlah minimal tap/like" />
                            <input type="number" min="1" wire:model="minCount" class="mt-1 block w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200">
                        </div>
                    @endif

                    @if ($selectedType->needsMappedGift())
                        <div class="relative" wire:key="gift-picker">
                            <x-input-label value="Gift yang muncul" />
                            <div class="flex items-center gap-2 mt-1">
                                <x-text-input wire:model.live.debounce.300ms="giftSearch" type="text" placeholder="Cari nama gift..." class="block w-full text-sm" />
                                @if ($giftId)
                                    <button type="button" wire:click="clearGiftPick" class="flex-shrink-0 text-xs text-gray-400 hover:text-red-500">Ganti</button>
                                @endif
                            </div>
                            @if ($giftResults->isNotEmpty())
                                <div class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg max-h-48 overflow-y-auto">
                                    @foreach ($giftResults as $result)
                                        <button type="button" wire:click="pickGift({{ $result->id }})"
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
                    @endif

                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model="active" class="rounded border-gray-300 dark:border-gray-600">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Aktif</span>
                    </label>
                @endif

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
