<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Preview Live
            </h2>
            <a href="{{ route('project-live.admin', $projectLive) }}" wire:navigate
                class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">&larr; Kembali ke Admin</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <div class="flex items-start justify-between gap-3">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    @if ($projectLive->auto_gift_mode)
                        Kursi diatur otomatis dari gift TikTok LIVE. Edit manual tetap bisa dipakai, tapi kursi bisa ketiban update otomatis berikutnya.
                    @else
                        Atur 8 kursi tamu untuk project ini. Klik salah satu kotak untuk mengubah foto, nama, coin, hotkey, dan status tampil/sembunyi.
                    @endif
                </p>
                <button wire:click="hideAll" wire:confirm="Sembunyikan semua kursi?" type="button"
                    class="flex-shrink-0 inline-flex items-center px-3 py-1.5 bg-gray-800 dark:bg-gray-700 text-white text-xs font-semibold rounded-md hover:bg-gray-700 dark:hover:bg-gray-600">
                    Hide All
                </button>
            </div>

            <!-- Kontainer ini SENGAJA meniru persis bentuk halaman Live asli (rasio +
                 grid-cols/rows sama dgn live-show.blade.php, lihat DisplayMode::
                 previewContainerClass()/previewGridClass()) - jadi bentuknya (potret/
                 lanskap) beneran berubah sesuai tata letak yang dipilih, bukan cuma
                 grid kotak generik. -->
            <div class="flex justify-center">
                <div class="{{ $projectLive->display_mode->previewContainerClass() }} grid {{ $projectLive->display_mode->previewGridClass() }} gap-3">
                    @foreach ($details as $detail)
                        <div wire:click="openEdit({{ $detail->id }})" role="button" tabindex="0"
                            class="relative w-full h-full rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-900 flex flex-col items-center justify-center gap-1 hover:ring-2 hover:ring-indigo-500 transition cursor-pointer">
                        <span class="absolute top-1.5 left-1.5 flex items-center gap-1">
                            <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-black/60 text-white">
                                #{{ $detail->position }}
                            </span>
                            @if ($detail->source->value === 'auto')
                                <span class="text-[9px] font-semibold px-1.5 py-0.5 rounded bg-indigo-600 text-white">
                                    AUTO
                                </span>
                            @endif
                            @php
                                $previewColor = $detail->status->value === 'show' ? $detail->dominant_color : ($detail->active_hotkey_color ?: '#000000');
                            @endphp
                            <span class="w-3 h-3 rounded-full border border-white/60 shadow" style="background: {{ $previewColor }};" title="{{ $previewColor }}"></span>
                        </span>

                        <!-- Toggle status: klik langsung ubah tanpa buka modal -->
                        <button type="button" wire:click.stop="toggleStatus({{ $detail->id }})"
                            title="{{ $detail->status->value === 'show' ? 'Klik untuk Hide' : 'Klik untuk Show' }}"
                            class="absolute top-1.5 right-1.5 flex items-center gap-1 rounded-full px-1 py-0.5 transition {{ $detail->status->value === 'show' ? 'bg-green-600' : 'bg-gray-400 dark:bg-gray-600' }}">
                            <span class="relative inline-flex h-3.5 w-6 items-center rounded-full bg-black/20">
                                <span class="inline-block h-2.5 w-2.5 transform rounded-full bg-white transition {{ $detail->status->value === 'show' ? 'translate-x-3' : 'translate-x-0.5' }}"></span>
                            </span>
                            <span class="text-[9px] font-semibold text-white pr-0.5">{{ $detail->status->value === 'show' ? 'Show' : 'Hide' }}</span>
                        </button>

                        @if ($detail->img)
                            <img src="{{ $detail->imgUrl() }}" class="w-20 h-20 rounded-full object-cover" alt="{{ $detail->name }}">
                        @else
                            <div class="w-20 h-20 rounded-full bg-gray-300 dark:bg-gray-700 flex items-center justify-center text-gray-500 dark:text-gray-400 text-2xl">
                                +
                            </div>
                        @endif

                        <span class="text-xs font-medium text-gray-700 dark:text-gray-300 truncate max-w-[90%]">
                            {{ $detail->name ?: 'Belum diisi' }}
                        </span>

                        <span class="text-[10px] text-gray-500 dark:text-gray-400">
                            {{ number_format($detail->gift_total_value) }} coin
                        </span>

                        @if ($detail->hotkey)
                            <span class="absolute bottom-1.5 right-1.5 text-[10px] font-mono px-1.5 py-0.5 rounded bg-indigo-600 text-white">
                                {{ $detail->hotkey }}
                            </span>
                        @endif
                        </div>
                    @endforeach
                </div>
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
                            <x-input-label value="Ikon" />
                            <div class="flex flex-wrap gap-2 mt-1">
                                @foreach (\App\Livewire\ProjectLive\PreviewLive::EMPTY_ICON_CHOICES as $icon)
                                    <button type="button" wire:click="$set('emptyIcon', '{{ $icon }}')"
                                        class="w-9 h-9 flex items-center justify-center text-lg rounded-md border transition {{ $emptyIcon === $icon ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30' : 'border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                                        {{ $icon }}
                                    </button>
                                @endforeach
                            </div>
                            <x-input-error :messages="$errors->get('emptyIcon')" class="mt-2" />
                        </div>

                        <p class="text-xs text-gray-400">
                            Warna latar kotak kosong sekarang diatur di halaman
                            <a href="{{ route('project-live.hotkey-color', $projectLive) }}" wire:navigate class="text-indigo-600 dark:text-indigo-400 hover:underline">Hotkey Warna</a>.
                        </p>
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
