<div @if ($projectLive->auto_gift_mode) wire:poll.5s="$refresh" @endif>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Admin — {{ $projectLive->name }}
            </h2>
            <div class="flex items-center gap-4">
                @can('manage', \App\Models\ProjectLive::class)
                    <a href="{{ route('project-live.index') }}" wire:navigate
                        class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">&larr; Kembali ke daftar project</a>
                @endcan
                <a href="{{ route('project-live.gift-mapping', $projectLive) }}" wire:navigate
                    class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">Pemetaan Gift</a>
                <a href="{{ route('project-live.frame-host', $projectLive) }}" wire:navigate
                    class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">Frame Host</a>
                <a href="{{ route('project-live.live', $projectLive) }}" wire:navigate
                    class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">Buka Live &rarr;</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="flex items-center justify-between gap-3">
                @can('manage', \App\Models\ProjectLive::class)
                    <button type="button" wire:click="toggleProjectLiveStatus"
                        title="{{ $projectLive->status->value === 'live' ? 'Klik untuk Off' : 'Klik untuk Live' }}"
                        class="inline-flex items-center gap-1.5 rounded-full pl-1 pr-3 py-1 transition {{ $projectLive->status->value === 'live' ? 'bg-green-600' : 'bg-gray-300 dark:bg-gray-600' }}">
                        <span class="relative inline-flex h-5 w-9 items-center rounded-full bg-black/20">
                            <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white transition {{ $projectLive->status->value === 'live' ? 'translate-x-[18px]' : 'translate-x-1' }}"></span>
                        </span>
                        <span class="text-sm font-semibold text-white">{{ $projectLive->status->value === 'live' ? 'Live' : 'Off' }}</span>
                    </button>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $projectLive->status->value === 'live' ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                        {{ $projectLive->status->value === 'live' ? 'Live' : 'Off' }}
                    </span>
                @endcan

                <div class="flex items-center gap-2">
                    @if ($projectLive->auto_gift_mode)
                        <button wire:click="resetLeaderboard" wire:confirm="Reset leaderboard? Semua kursi auto akan dikosongkan." type="button"
                            class="flex-shrink-0 inline-flex items-center px-3 py-1.5 bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-xs font-semibold rounded-md hover:bg-red-100 dark:hover:bg-red-900/50">
                            Reset Leaderboard
                        </button>
                    @endif
                    <button wire:click="hideAll" wire:confirm="Sembunyikan semua kursi?" type="button"
                        class="flex-shrink-0 inline-flex items-center px-3 py-1.5 bg-gray-800 dark:bg-gray-700 text-white text-xs font-semibold rounded-md hover:bg-gray-700 dark:hover:bg-gray-600">
                        Hide All
                    </button>
                </div>
            </div>

            @can('manage', \App\Models\ProjectLive::class)
                <!-- Tata Letak Halaman Live -->
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 space-y-3">
                    <div>
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Tata Letak Halaman Live</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Menentukan susunan kursi di halaman Live (yang dibuka lewat "Buka Live").</p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                        @foreach (\App\Enums\DisplayMode::cases() as $mode)
                            <button type="button" wire:click="updateDisplayMode('{{ $mode->value }}')"
                                class="text-left px-3 py-2 rounded-md border text-xs font-medium transition {{ $projectLive->display_mode === $mode ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300' : 'border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                                {{ $mode->label() }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @endcan

            <!-- Ukuran Konten Kotak Live: beda dari Tata Letak di atas (superadmin only),
                 ini boleh diatur akun role "live" juga -->
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 space-y-3">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Ukuran Konten Kotak Live</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Persen dari ukuran default (100%). Berlaku untuk semua kotak kursi di halaman Live.</p>
                    </div>
                    <button type="button" wire:click="resetSizes"
                        class="flex-shrink-0 text-xs font-semibold text-gray-400 hover:text-red-500">
                        Reset ke Default
                    </button>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    @foreach ([
                        'coin' => 'Coin (badge)',
                        'name' => 'Nama (badge)',
                        'avatar' => 'Foto',
                        'empty_icon' => 'Icon kotak kosong',
                        'empty_label' => 'Teks kotak kosong',
                        'gift_badge' => 'Icon pemetaan gift',
                    ] as $field => $label)
                        <div>
                            <x-input-label :for="'size-'.$field" :value="$label" />
                            <div class="flex items-center gap-1 mt-1">
                                <x-text-input :id="'size-'.$field" wire:model="sizes.{{ $field }}" type="number" min="50" max="200" step="5" class="block w-full text-sm" />
                                <span class="text-xs text-gray-400 flex-shrink-0">%</span>
                            </div>
                            <x-input-error :messages="$errors->get('sizes.'.$field)" class="mt-1" />
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-end">
                    <button type="button" wire:click="saveSizes"
                        class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded-md hover:bg-indigo-700">
                        Simpan Ukuran
                    </button>
                </div>
            </div>

            <!-- Auto Gift Mode -->
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Auto Gift Mode</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Kursi otomatis terisi dari leaderboard gift TikTok LIVE, tanpa hotkey manual.</p>
                    </div>
                    <button type="button" wire:click="toggleAutoGiftMode"
                        class="flex-shrink-0 inline-flex items-center gap-1.5 rounded-full pl-1 pr-3 py-1 transition {{ $projectLive->auto_gift_mode ? 'bg-green-600' : 'bg-gray-300 dark:bg-gray-600' }}">
                        <span class="relative inline-flex h-5 w-9 items-center rounded-full bg-black/20">
                            <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white transition {{ $projectLive->auto_gift_mode ? 'translate-x-[18px]' : 'translate-x-1' }}"></span>
                        </span>
                        <span class="text-sm font-semibold text-white">{{ $projectLive->auto_gift_mode ? 'ON' : 'OFF' }}</span>
                    </button>
                </div>

                @if ($projectLive->auto_gift_mode)
                    <div class="flex items-center gap-1.5 text-xs font-medium {{ $projectLive->isGiftListenerOnline() ? 'text-green-600 dark:text-green-400' : 'text-gray-400 dark:text-gray-500' }}">
                        <span class="w-2 h-2 rounded-full {{ $projectLive->isGiftListenerOnline() ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600' }}"></span>
                        {{ $projectLive->isGiftListenerOnline() ? 'Terhubung ke TikTok LIVE' : 'Belum terhubung — jalankan services/tiktok-gift-listener' }}
                    </div>

                    <div class="border-t border-gray-100 dark:border-gray-700 pt-4 space-y-4">
                        <form wire:submit="saveTikTokUsername" class="flex items-end gap-2">
                            <div class="flex-1">
                                <x-input-label for="tiktokUsername" value="Username TikTok LIVE (tanpa @)" />
                                <x-text-input wire:model="tiktokUsername" id="tiktokUsername" class="block mt-1 w-full" type="text" placeholder="namaakun" />
                                <x-input-error :messages="$errors->get('tiktokUsername')" class="mt-2" />
                            </div>
                            <x-primary-button>Simpan</x-primary-button>
                        </form>

                        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-md p-3 space-y-2 text-xs" x-data="{ copied: false }">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-gray-500 dark:text-gray-400">
                                    Copy langsung ke <code class="font-mono">.env</code> di <code class="font-mono">services/tiktok-gift-listener</code>:
                                </p>
                                <button type="button"
                                    x-on:click="navigator.clipboard.writeText($refs.envBlock.innerText); copied = true; setTimeout(() => copied = false, 1500)"
                                    class="flex-shrink-0 inline-flex items-center px-2 py-1 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 text-[10px] font-semibold rounded hover:bg-indigo-100 dark:hover:bg-indigo-900/50">
                                    <span x-text="copied ? 'Tersalin!' : 'Copy'"></span>
                                </button>
                            </div>
                            <pre x-ref="envBlock" class="font-mono text-gray-700 dark:text-gray-300 whitespace-pre-wrap break-all select-all bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded p-2">TIKTOK_USERNAME={{ $projectLive->tiktok_username }}
PROJECT_LIVE_ID={{ $projectLive->id }}
WEBHOOK_URL={{ url('/api/webhooks/tiktok-gift') }}
WEBHOOK_SECRET="{{ $projectLive->webhook_secret }}"</pre>
                        </div>

                        <!-- Katalog & aturan gift -->
                        <div class="space-y-3">
                            <div>
                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Gift yang Dihitung</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Katalog: {{ $giftCatalogCount }} jenis gift.
                                    @if ($giftCatalogUpdatedAt)
                                        Terakhir update {{ $giftCatalogUpdatedAt->diffForHumans() }}.
                                    @else
                                        Belum ada data.
                                    @endif
                                </p>
                            </div>

                            @if ($giftCatalogCount === 0)
                                <p class="text-xs text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 rounded-md p-2">
                                    Katalog masih kosong. Jalankan <code class="font-mono">php artisan db:seed --class=TikTokGiftSeeder</code> untuk mengisi daftar gift.
                                </p>
                            @else
                                <x-text-input wire:model.live.debounce.300ms="giftSearch" type="text" placeholder="Cari nama gift..." class="block w-full text-sm" />

                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-xs text-gray-400">
                                        Menampilkan {{ count($gifts) }} dari {{ number_format($giftMatchCount) }} gift{{ $giftSearch ? ' (hasil pencarian)' : '' }}
                                        @if ($giftMatchCount > count($gifts))
                                            — ketik nama buat mempersempit
                                        @endif
                                    </p>
                                    <div class="flex items-center gap-2">
                                        <button type="button" wire:click="enableAllGifts"
                                            class="inline-flex items-center px-2.5 py-1 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-400 text-xs font-semibold rounded-md hover:bg-green-100 dark:hover:bg-green-900/40">
                                            Aktifkan Semua
                                        </button>
                                        <button type="button" wire:click="disableAllGifts" wire:confirm="Nonaktifkan semua gift {{ $giftSearch ? 'hasil pencarian ini' : 'di katalog' }}?"
                                            class="inline-flex items-center px-2.5 py-1 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 text-xs font-semibold rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
                                            Nonaktifkan Semua
                                        </button>
                                    </div>
                                </div>

                                <div class="max-h-64 overflow-y-auto border border-gray-100 dark:border-gray-700 rounded-md divide-y divide-gray-100 dark:divide-gray-700">
                                    @forelse ($gifts as $gift)
                                        @php $isEnabled = in_array($gift->id, $enabledGiftIds); @endphp
                                        <div wire:key="gift-{{ $gift->id }}" class="w-full flex items-center gap-3 px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-900/50">
                                            <div wire:click="toggleGiftRule({{ $gift->id }})" role="button" tabindex="0"
                                                class="flex items-center gap-3 flex-1 min-w-0 text-left cursor-pointer">
                                                @if ($gift->icon_url)
                                                    <img src="{{ $gift->icon_url }}" class="w-6 h-6 rounded flex-shrink-0" alt="">
                                                @else
                                                    <div class="w-6 h-6 rounded bg-gray-200 dark:bg-gray-700 flex-shrink-0"></div>
                                                @endif
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm text-gray-800 dark:text-gray-200 truncate">{{ $gift->name }}</p>
                                                    @if ($editingGiftId !== $gift->id)
                                                        <p class="text-xs text-gray-400">{{ number_format($gift->diamond_count) }} diamond</p>
                                                    @endif
                                                </div>
                                                <span class="relative inline-flex h-5 w-9 items-center rounded-full flex-shrink-0 transition {{ $isEnabled ? 'bg-green-600' : 'bg-gray-300 dark:bg-gray-600' }}">
                                                    <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white transition {{ $isEnabled ? 'translate-x-[18px]' : 'translate-x-1' }}"></span>
                                                </span>
                                            </div>

                                            @can('viewLive', $projectLive)
                                                @if ($editingGiftId === $gift->id)
                                                    <div wire:key="gift-actions-{{ $gift->id }}-edit" class="flex items-center gap-1.5 flex-shrink-0" wire:click.stop>
                                                        <input type="number" min="0" wire:model="giftDiamondCount" wire:keydown.enter="saveGiftDiamond"
                                                            class="w-28 text-xs text-white placeholder-gray-400 rounded border-gray-300 dark:border-gray-600 bg-gray-700 dark:bg-gray-900 py-1 px-2">
                                                        <button type="button" wire:click="saveGiftDiamond"
                                                            class="text-[10px] font-semibold text-green-600 dark:text-green-400 hover:underline">Simpan</button>
                                                        <button type="button" wire:click="cancelEditGiftDiamond"
                                                            class="text-[10px] font-semibold text-gray-400 hover:underline">Batal</button>
                                                    </div>
                                                @else
                                                    <div wire:key="gift-actions-{{ $gift->id }}-view" class="flex items-center gap-1 flex-shrink-0">
                                                        <button type="button" wire:click="openEditGiftDiamond({{ $gift->id }})" title="Edit coin"
                                                            class="p-1 rounded text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30">
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5">
                                                                <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793ZM11.379 5.793 3 14.172V17h2.828l8.38-8.379-2.83-2.828Z" />
                                                            </svg>
                                                        </button>
                                                        <button type="button" wire:click="deleteGift({{ $gift->id }})"
                                                            wire:confirm="Hapus gift &quot;{{ $gift->name }}&quot; dari katalog? Ini menghapusnya untuk semua project."
                                                            title="Hapus gift"
                                                            class="p-1 rounded text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30">
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5">
                                                                <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5ZM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4ZM8.58 7.72a.75.75 0 00-1.5.06l.3 7.5a.75.75 0 101.5-.06l-.3-7.5Zm4.34.06a.75.75 0 10-1.5-.06l-.3 7.5a.75.75 0 101.5.06l.3-7.5Z" clip-rule="evenodd" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                @endif
                                            @endcan
                                        </div>
                                    @empty
                                        <p class="text-xs text-gray-400 px-3 py-4 text-center">Tidak ada gift yang cocok dengan pencarian.</p>
                                    @endforelse
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <div class="flex items-start justify-between gap-3">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    @if ($projectLive->auto_gift_mode)
                        Kursi diatur otomatis dari gift TikTok LIVE. Edit manual tetap bisa dipakai, tapi kursi bisa ketiban update otomatis berikutnya.
                    @else
                        Atur 8 kursi tamu untuk project ini. Klik salah satu kotak untuk mengubah foto, nama, coin, hotkey, dan status tampil/sembunyi.
                    @endif
                </p>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                @foreach ($details as $detail)
                    <div wire:click="openEdit({{ $detail->id }})" role="button" tabindex="0"
                        class="relative aspect-square rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-900 flex flex-col items-center justify-center gap-1 hover:ring-2 hover:ring-indigo-500 transition cursor-pointer">
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
                                $previewColor = $detail->status->value === 'show' ? $detail->dominant_color : ($detail->empty_bg_color ?: '#000000');
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
                                @foreach (\App\Livewire\ProjectLive\DetailAdmin::EMPTY_ICON_CHOICES as $icon)
                                    <button type="button" wire:click="$set('emptyIcon', '{{ $icon }}')"
                                        class="w-9 h-9 flex items-center justify-center text-lg rounded-md border transition {{ $emptyIcon === $icon ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30' : 'border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                                        {{ $icon }}
                                    </button>
                                @endforeach
                            </div>
                            <x-input-error :messages="$errors->get('emptyIcon')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="emptyBgColor" value="Warna latar" />
                            <div class="flex items-center gap-2 mt-1">
                                <input type="color" wire:model.live="emptyBgColor" id="emptyBgColor" class="w-10 h-9 rounded border border-gray-300 dark:border-gray-600 bg-transparent p-0.5">
                                <x-text-input wire:model.live.debounce.300ms="emptyBgColor" type="text" class="block w-full text-sm font-mono" maxlength="7" placeholder="#000000" />
                            </div>
                            <x-input-error :messages="$errors->get('emptyBgColor')" class="mt-2" />
                        </div>
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
