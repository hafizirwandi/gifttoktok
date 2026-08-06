<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Admin — {{ $projectLive->name }}
            </h2>
            <a href="{{ route('project-live.index') }}" wire:navigate
                class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">&larr; Kembali ke daftar project</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Atur 8 kursi tamu untuk project ini. Klik salah satu kotak untuk mengubah foto, nama, follower, hotkey, dan status tampil/sembunyi.
            </p>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                @foreach ($details as $detail)
                    <button type="button" wire:click="openEdit({{ $detail->id }})"
                        class="relative aspect-square rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-900 flex flex-col items-center justify-center gap-1 hover:ring-2 hover:ring-indigo-500 transition text-left">
                        <span class="absolute top-1.5 left-1.5 text-[10px] font-semibold px-1.5 py-0.5 rounded bg-black/60 text-white">
                            #{{ $detail->position }}
                        </span>

                        <span class="absolute top-1.5 right-1.5 text-[10px] font-semibold px-1.5 py-0.5 rounded {{ $detail->status->value === 'show' ? 'bg-green-600 text-white' : 'bg-gray-500 text-white' }}">
                            {{ $detail->status->value === 'show' ? 'Show' : 'Hide' }}
                        </span>

                        @if ($detail->img)
                            <img src="{{ $detail->imgUrl() }}" class="w-14 h-14 rounded-full object-cover" alt="{{ $detail->name }}">
                        @else
                            <div class="w-14 h-14 rounded-full bg-gray-300 dark:bg-gray-700 flex items-center justify-center text-gray-500 dark:text-gray-400 text-xl">
                                +
                            </div>
                        @endif

                        <span class="text-xs font-medium text-gray-700 dark:text-gray-300 truncate max-w-[90%]">
                            {{ $detail->name ?: 'Belum diisi' }}
                        </span>

                        @if ($detail->hotkey)
                            <span class="absolute bottom-1.5 right-1.5 text-[10px] font-mono px-1.5 py-0.5 rounded bg-indigo-600 text-white">
                                {{ $detail->hotkey }}
                            </span>
                        @endif
                    </button>
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
                class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6 space-y-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    Edit Kursi
                </h3>

                <form wire:submit="save" class="space-y-4">
                    <div>
                        <x-input-label for="img" value="Foto" />
                        <input type="file" wire:model="img" id="img" accept="image/*"
                            class="block mt-1 w-full text-sm text-gray-600 dark:text-gray-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 dark:file:bg-indigo-900/40 file:text-indigo-700 dark:file:text-indigo-300">
                        <div wire:loading wire:target="img" class="text-xs text-gray-400 mt-1">Mengunggah...</div>
                        @if ($img)
                            <img src="{{ $img->temporaryUrl() }}" class="w-16 h-16 rounded-full object-cover mt-2">
                        @endif
                        <x-input-error :messages="$errors->get('img')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="name" value="Nama" />
                        <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <x-input-label for="follower" value="Follower" />
                            <x-text-input wire:model="follower" id="follower" class="block mt-1 w-full" type="text" placeholder="754,7K" />
                            <x-input-error :messages="$errors->get('follower')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="hotkey" value="Hotkey" />
                            <x-text-input wire:model="hotkey" id="hotkey" maxlength="1" class="block mt-1 w-full" type="text" placeholder="1" />
                            <x-input-error :messages="$errors->get('hotkey')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="status" value="Status" />
                        <select wire:model="status" id="status"
                            class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="hide">Hide</option>
                            <option value="show">Show</option>
                        </select>
                        <x-input-error :messages="$errors->get('status')" class="mt-2" />
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
