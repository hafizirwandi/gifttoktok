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
                <a href="{{ route('project-live.hotkey-color', $projectLive) }}" wire:navigate
                    class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">Hotkey Warna</a>
                <a href="{{ route('project-live.event-trigger', $projectLive) }}" wire:navigate
                    class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">Event Trigger</a>
                <a href="{{ route('project-live.background', $projectLive) }}" wire:navigate
                    class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">Background</a>
                <a href="{{ route('project-live.preview-live', $projectLive) }}" target="_blank" rel="noopener"
                    class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">Preview Live</a>
                <a href="{{ route('project-live.live', $projectLive) }}" target="_blank" rel="noopener"
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
                    <button wire:click="resetCoins" wire:confirm="Reset semua coin ke 0? Nama & kursi yang sedang tampil tidak berubah." type="button"
                        class="flex-shrink-0 inline-flex items-center px-3 py-1.5 bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 text-xs font-semibold rounded-md hover:bg-amber-100 dark:hover:bg-amber-900/50">
                        Reset Coin
                    </button>
                    <button wire:click="resetLeaderboard" wire:confirm="Reset leaderboard? Semua kursi akan dikosongkan (coin & data gifter tidak ikut terhapus)." type="button"
                        class="flex-shrink-0 inline-flex items-center px-3 py-1.5 bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-xs font-semibold rounded-md hover:bg-red-100 dark:hover:bg-red-900/50">
                        Reset Leaderboard
                    </button>
                </div>
            </div>

            <!-- Hotkey Reset Leaderboard/Coin: dipencet di halaman LIVE (bukan di sini)
                 supaya operator tidak perlu pindah tab saat siaran, langsung eksekusi
                 tanpa konfirmasi (sama seperti hotkey warna/reveal kursi lainnya). -->
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 space-y-3">
                <div>
                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Hotkey Reset (di halaman Live)</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Pencet huruf/angka ini saat membuka halaman Live buat langsung Reset Leaderboard/Reset Coin —
                        tanpa konfirmasi, jadi langsung jalan begitu ditekan. Kosongkan kalau tidak mau dipakai.
                    </p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <x-input-label value="Hotkey Reset Leaderboard" />
                        <x-text-input wire:model="resetLeaderboardHotkey" type="text" maxlength="1" placeholder="mis. l" class="block w-full text-sm mt-1 uppercase" />
                        @error('resetLeaderboardHotkey') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <x-input-label value="Hotkey Reset Coin" />
                        <x-text-input wire:model="resetCoinHotkey" type="text" maxlength="1" placeholder="mis. k" class="block w-full text-sm mt-1 uppercase" />
                        @error('resetCoinHotkey') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="flex justify-end">
                    <button type="button" wire:click="saveResetHotkeys"
                        class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded-md hover:bg-indigo-700">
                        Simpan Hotkey
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
                    <!-- Kartu ikon (bukan cuma teks) - klik langsung simpan spt sebelumnya. Preview
                         kursinya sendiri ada di menu "Preview Live" terpisah. Ganti tata letak SELALU
                         minta konfirmasi (bukan cuma pas kursi berkurang) krn updateDisplayMode() juga
                         reset leaderboard sekalian - lihat App\Livewire\ProjectLive\DetailAdmin. Pakai
                         CSS grid dgn JUMLAH KOLOM TETAP per breakpoint (bukan flex-wrap/auto-fill) -
                         semua kartu jadi otomatis SAMA PERSIS ukurannya & sejajar rapi (lebar kartu =
                         lebar track grid, seragam), beda dari flex-1 sebelumnya yg bisa melebar beda2
                         tiap baris. -->
                    <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-2">
                        @foreach (\App\Enums\DisplayMode::cases() as $mode)
                            <button type="button" wire:click="updateDisplayMode('{{ $mode->value }}')"
                                title="{{ $mode->description() }}"
                                wire:confirm="Ganti ke &quot;{{ $mode->label() }}&quot;? Leaderboard akan direset (papan dikosongkan), dan kalau kursi jadi lebih sedikit dari sekarang, kursi yang hilang beserta hotkey &amp; warna kustom di kotak itu akan terhapus permanen. Lanjutkan?"
                                class="flex flex-col items-center gap-1 p-2 rounded-md border transition {{ $projectLive->display_mode === $mode ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30' : 'border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                                <img src="{{ asset($mode->iconPath()) }}" alt="{{ $mode->label() }}" class="w-8 h-14 object-contain">
                                <span class="text-[10px] font-medium text-center leading-tight {{ $projectLive->display_mode === $mode ? 'text-indigo-700 dark:text-indigo-300' : 'text-gray-500 dark:text-gray-400' }}">{{ $mode->label() }}</span>
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
                        'mic' => 'Icon mic',
                    ] as $field => $label)
                        <div>
                            <x-input-label :for="'size-'.$field" :value="$label" />
                            <div class="flex items-center gap-1 mt-1">
                                <x-text-input :id="'size-'.$field" wire:model="sizes.{{ $field }}" type="number" min="50" max="200" step="5" class="block w-full text-sm" />
                                <span class="text-xs text-gray-400 flex-shrink-0">%</span>
                            </div>
                            <x-input-error :messages="$errors->get('sizes.'.$field)" class="mt-1" />
                            @if ($field === 'mic')
                                <p class="text-[10px] text-gray-400 mt-1">Nyala/mati mic diatur per kotak di Preview Live.</p>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-end">
                    <button type="button" wire:click="saveSizes"
                        class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded-md hover:bg-indigo-700">
                        Simpan Ukuran
                    </button>
                </div>

                <!-- Padding, tebal border, & rounded border kotak - beda dari grid ukuran di
                     atas (persen skala), ini nilai PIXEL literal, lihat
                     App\Livewire\ProjectLive\DetailAdmin::BOX_STYLE_FIELDS. -->
                <div class="border-t border-gray-100 dark:border-gray-700 pt-3 space-y-3">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-xs font-semibold text-gray-600 dark:text-gray-300">Padding &amp; Border Kotak</p>
                        <button type="button" wire:click="resetBoxStyle"
                            class="flex-shrink-0 text-xs font-semibold text-gray-400 hover:text-red-500">
                            Reset ke Default
                        </button>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        @foreach (\App\Livewire\ProjectLive\DetailAdmin::BOX_STYLE_FIELDS as $field => $config)
                            <div>
                                <x-input-label :for="'box-style-'.$field" :value="$config['label']" />
                                <div class="flex items-center gap-1 mt-1">
                                    <x-text-input :id="'box-style-'.$field" wire:model="boxStyle.{{ $field }}" type="number" :min="$config['min']" :max="$config['max']" step="1" class="block w-full text-sm" />
                                    <span class="text-xs text-gray-400 flex-shrink-0">px</span>
                                </div>
                                <x-input-error :messages="$errors->get('boxStyle.'.$field)" class="mt-1" />
                            </div>
                        @endforeach
                    </div>

                    <div class="flex justify-end">
                        <button type="button" wire:click="saveBoxStyle"
                            class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded-md hover:bg-indigo-700">
                            Simpan Padding &amp; Border
                        </button>
                    </div>
                </div>

                <!-- Icon mic custom - satu utk SEMUA kotak kursi (beda dari icon kotak
                     kosong di Preview Live yang per-kotak), lihat
                     App\Models\ProjectLive::micIconUrl(). Ukuran/posisi tetap pakai
                     "Icon mic" di grid Ukuran Konten & "Naik/Turun Icon Mic" di atas. -->
                <div class="border-t border-gray-100 dark:border-gray-700 pt-3 space-y-3">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold text-gray-600 dark:text-gray-300">Icon Mic Custom</p>
                            <p class="text-[10px] text-gray-400">Ganti icon mic bawaan (SVG) dengan gambar sendiri, berlaku ke semua kotak.</p>
                        </div>
                        @if ($projectLive->mic_icon)
                            <img src="{{ $projectLive->micIconUrl() }}" alt="Icon mic" class="w-8 h-8 object-contain flex-shrink-0">
                        @endif
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="file" wire:model="micIconFile" accept="image/*"
                            class="block w-full text-sm text-gray-600 dark:text-gray-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 dark:file:bg-indigo-900/40 file:text-indigo-700 dark:file:text-indigo-300">
                        <button type="button" wire:click="saveMicIcon"
                            class="flex-shrink-0 inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded-md hover:bg-indigo-700">
                            Simpan
                        </button>
                        @if ($projectLive->mic_icon)
                            <button type="button" wire:click="removeMicIcon" wire:confirm="Kembalikan ke icon mic bawaan?"
                                class="flex-shrink-0 text-xs font-semibold text-gray-400 hover:text-red-500">
                                Hapus
                            </button>
                        @endif
                    </div>
                    <p class="text-xs text-gray-400">JPG, PNG, atau WEBP, maksimal 8MB.</p>
                    <div wire:loading wire:target="micIconFile" class="text-xs text-gray-400">Mengunggah...</div>
                    <x-input-error :messages="$errors->get('micIconFile')" class="mt-1" />
                </div>

                <!-- Arah kotak kosong diisi gifter baru - lihat
                     App\Services\GiftLeaderboardService::recalculate(). -->
                <div class="border-t border-gray-100 dark:border-gray-700 pt-3 space-y-2">
                    <p class="text-xs font-semibold text-gray-600 dark:text-gray-300">Urutan Kotak Diisi Gift Baru</p>
                    <div class="grid grid-cols-1 gap-2">
                        @foreach (\App\Enums\SeatFillDirection::cases() as $direction)
                            <button type="button" wire:click="updateSeatFillDirection('{{ $direction->value }}')"
                                class="text-left px-3 py-2 rounded-md border text-xs font-medium transition {{ $projectLive->seat_fill_direction === $direction ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300' : 'border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                                {{ $direction->label() }}
                            </button>
                        @endforeach
                    </div>
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

                            @if (! $showCustomGiftForm)
                                <button type="button" wire:click="openCustomGiftForm"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 text-indigo-700 dark:text-indigo-400 text-xs font-semibold rounded-md hover:bg-indigo-100 dark:hover:bg-indigo-900/40">
                                    + Tambah Gift Custom
                                </button>
                            @else
                                <div class="space-y-2 border border-indigo-200 dark:border-indigo-800 rounded-md p-3 bg-indigo-50/50 dark:bg-indigo-900/10">
                                    <p class="text-xs font-semibold text-indigo-700 dark:text-indigo-400">Gift Custom Baru</p>

                                    <div>
                                        <x-text-input wire:model="customGiftName" type="text" placeholder="Nama gift" class="block w-full text-sm" />
                                        @error('customGiftName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                    </div>

                                    <div>
                                        <input type="number" min="0" wire:model="customGiftDiamondCount" placeholder="Nilai coin"
                                            class="block w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                                        @error('customGiftDiamondCount') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="flex items-center gap-3 text-xs">
                                        <label class="flex items-center gap-1 cursor-pointer">
                                            <input type="radio" wire:model.live="customGiftIconMode" value="upload"> Upload ikon
                                        </label>
                                        <label class="flex items-center gap-1 cursor-pointer">
                                            <input type="radio" wire:model.live="customGiftIconMode" value="url"> Link gambar
                                        </label>
                                    </div>

                                    @if ($customGiftIconMode === 'upload')
                                        <div>
                                            <input type="file" wire:model="customGiftIcon" accept="image/png,image/jpeg,image/webp"
                                                class="block w-full text-xs text-gray-600 dark:text-gray-300 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-indigo-100 file:text-indigo-700 dark:file:bg-indigo-900/40 dark:file:text-indigo-300">
                                            <p class="text-[10px] text-gray-400 mt-0.5">JPG, PNG, atau WEBP, maksimal 8MB.</p>
                                            @error('customGiftIcon') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                        </div>
                                    @else
                                        <div>
                                            <x-text-input wire:model="customGiftIconUrl" type="text" placeholder="https://.../ikon.png" class="block w-full text-sm" />
                                            @error('customGiftIconUrl') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                        </div>
                                    @endif

                                    <div class="flex items-center gap-3 pt-1">
                                        <button type="button" wire:click="saveCustomGift"
                                            class="text-xs font-semibold text-green-600 dark:text-green-400 hover:underline">Simpan</button>
                                        <button type="button" wire:click="closeCustomGiftForm"
                                            class="text-xs font-semibold text-gray-400 hover:underline">Batal</button>
                                    </div>
                                </div>
                            @endif

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
                                                    <p class="text-sm text-gray-800 dark:text-gray-200 truncate">
                                                        {{ $gift->name }}
                                                        @if ($gift->is_custom)
                                                            <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-400">Custom</span>
                                                        @endif
                                                    </p>
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
        </div>
    </div>
</div>
