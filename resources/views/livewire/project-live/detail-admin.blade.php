<div @if ($projectLive->auto_gift_mode) wire:poll.5s="$refresh" @endif>
    <x-slot name="header">
        @include('livewire.project-live.partials.nav', ['projectLive' => $projectLive, 'title' => 'Admin'])
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
                {{-- Tata Letak Halaman Live - disembunyikan default (x-data lokal, bukan
                     Livewire) krn grid ikonnya makan tempat banyak & jarang diganti-ganti,
                     beda dari settingan lain di halaman ini yang lebih sering di-tweak. --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 space-y-3" x-data="{ open: false }">
                    <button type="button" x-on:click="open = ! open" class="w-full flex items-center justify-between gap-3 text-left">
                        <div>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Tata Letak Halaman Live</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Menentukan susunan kursi di halaman Live (yang dibuka lewat "Buka Live"). Saat ini: {{ $projectLive->display_mode->label() }}.</p>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 flex-shrink-0 text-gray-400 transition-transform" x-bind:class="open ? 'rotate-180' : ''">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    {{-- Kartu ikon (bukan cuma teks) - klik langsung simpan spt sebelumnya. Preview
                         kursinya sendiri ada di menu "Preview Live" terpisah. Ganti tata letak SELALU
                         minta konfirmasi (bukan cuma pas kursi berkurang) krn updateDisplayMode() juga
                         reset leaderboard sekalian - lihat App\Livewire\ProjectLive\DetailAdmin. Pakai
                         CSS grid dgn JUMLAH KOLOM TETAP per breakpoint (bukan flex-wrap/auto-fill) -
                         semua kartu jadi otomatis SAMA PERSIS ukurannya & sejajar rapi (lebar kartu =
                         lebar track grid, seragam), beda dari flex-1 sebelumnya yg bisa melebar beda2
                         tiap baris. --}}
                    <div x-show="open" x-cloak x-transition class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-2">
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

            {{-- Ukuran & Posisi Kotak Live: beda dari Tata Letak di atas (superadmin only),
                 ini boleh diatur akun role "live" juga. Dikelompokkan JADI SATU KARTU per
                 elemen (App\Livewire\ProjectLive\DetailAdmin::ELEMENT_GROUPS) - ukuran,
                 geser kiri/kanan, & naik/turun elemen yang SAMA sekarang keliatan bareng,
                 bukan tersebar di 2 grid terpisah spt sebelumnya (grid ukuran vs grid
                 padding/offset). Font Nama Host & Icon Mic Custom juga ikut dipindah ke
                 kartu elemennya masing2 (host_name/mic) krn nyambung sama elemen itu. --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 space-y-3">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Ukuran &amp; Posisi Kotak Live</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Berlaku ke semua kotak, kecuali kotak yang di-custom lokal lewat tombol "Custom" di Preview Live.</p>
                    </div>
                    <button type="button" wire:click="resetContentSettings"
                        class="flex-shrink-0 text-xs font-semibold text-gray-400 hover:text-red-500">
                        Reset ke Default
                    </button>
                </div>

                {{-- SATU form utk SELURUH settingan global di kartu ini (ukuran/posisi
                     per elemen, padding/border/gap, warna kotak, font, icon) - SATU
                     tombol Simpan di paling bawah, lihat DetailAdmin::saveGlobalSettings(). --}}
                <form wire:submit="saveGlobalSettings" class="space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach (\App\Livewire\ProjectLive\DetailAdmin::ELEMENT_GROUPS as $field => $label)
                        <div class="border border-gray-200 dark:border-gray-700 rounded-md p-3 space-y-2">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ $label }}</p>
                                {{-- Show/hide GLOBAL - fallback kalau override LOKAL kotak (tombol
                                     "Custom" di Preview Live) tidak diaktifkan, lihat
                                     App\Support\SeatStyleResolver::isVisible(). --}}
                                @if (in_array($field, \App\Livewire\ProjectLive\DetailAdmin::VISIBILITY_FIELDS, true))
                                    <button type="button" wire:click="toggleVisibilityDraft('{{ $field }}')"
                                        class="flex-shrink-0 inline-flex items-center gap-1.5 rounded-full pl-1 pr-2 py-0.5 transition {{ ($visibility[$field] ?? true) ? 'bg-green-600' : 'bg-gray-300 dark:bg-gray-600' }}">
                                        <span class="relative inline-flex h-4 w-7 items-center rounded-full bg-black/20">
                                            <span class="inline-block h-3 w-3 transform rounded-full bg-white transition {{ ($visibility[$field] ?? true) ? 'translate-x-3.5' : 'translate-x-0.5' }}"></span>
                                        </span>
                                        <span class="text-[10px] font-semibold text-white">{{ ($visibility[$field] ?? true) ? 'Tampil' : 'Sembunyi' }}</span>
                                    </button>
                                @endif
                            </div>

                            <div class="grid grid-cols-3 gap-2">
                                <div>
                                    <x-input-label value="Ukuran (%)" class="text-[10px]" />
                                    <x-text-input :id="'size-'.$field" wire:model="sizes.{{ $field }}" type="number" min="50" max="200" step="5" class="block w-full text-sm mt-0.5" />
                                </div>
                                <div>
                                    <x-input-label value="Kiri/Kanan" class="text-[10px]" />
                                    <x-text-input wire:model="boxStyle.{{ $field }}_offset_x" type="number" min="-100" max="100" class="block w-full text-sm mt-0.5" />
                                </div>
                                <div>
                                    <x-input-label value="Naik/Turun" class="text-[10px]" />
                                    <x-text-input wire:model="boxStyle.{{ $field }}_offset_y" type="number" min="-100" max="100" class="block w-full text-sm mt-0.5" />
                                </div>
                            </div>
                            <x-input-error :messages="$errors->get('sizes.'.$field)" class="mt-1" />
                            <x-input-error :messages="$errors->get('boxStyle.'.$field.'_offset_x')" class="mt-1" />
                            <x-input-error :messages="$errors->get('boxStyle.'.$field.'_offset_y')" class="mt-1" />

                            @if ($field === 'mic')
                                <p class="text-[10px] text-gray-400">Nyala/mati &amp; icon lokal per-kotak diatur di Preview Live.</p>
                                <div class="border-t border-gray-100 dark:border-gray-700 pt-2 space-y-1.5">
                                    <p class="text-[10px] font-semibold text-gray-500 dark:text-gray-400">Icon Custom (semua kotak)</p>
                                    <div class="flex items-center gap-1.5">
                                        @if ($projectLive->mic_icon)
                                            <img src="{{ $projectLive->micIconUrl() }}" alt="" class="w-6 h-6 object-contain flex-shrink-0">
                                        @endif
                                        <input type="file" wire:model="micIconFile" accept="image/*"
                                            class="block w-full text-[10px] text-gray-600 dark:text-gray-300 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-[10px] file:font-semibold file:bg-indigo-50 dark:file:bg-indigo-900/40 file:text-indigo-700 dark:file:text-indigo-300">
                                        @if ($projectLive->mic_icon)
                                            <button type="button" wire:click="removeMicIcon" wire:confirm="Kembalikan ke icon mic bawaan?"
                                                class="flex-shrink-0 text-[10px] font-semibold text-gray-400 hover:text-red-500">
                                                Hapus
                                            </button>
                                        @endif
                                    </div>
                                    <div wire:loading wire:target="micIconFile" class="text-[10px] text-gray-400">Mengunggah...</div>
                                    <x-input-error :messages="$errors->get('micIconFile')" class="mt-1" />
                                </div>
                            @endif

                            @if ($field === 'empty_icon')
                                <p class="text-[10px] text-gray-400">Icon lokal per-kotak diatur di Preview Live.</p>
                                <div class="border-t border-gray-100 dark:border-gray-700 pt-2 space-y-1.5">
                                    <p class="text-[10px] font-semibold text-gray-500 dark:text-gray-400">Icon Custom (semua kotak)</p>
                                    <div class="flex items-center gap-1.5">
                                        @if ($projectLive->empty_icon)
                                            <img src="{{ $projectLive->emptyIconUrl() }}" alt="" class="w-6 h-6 object-contain flex-shrink-0">
                                        @endif
                                        <input type="file" wire:model="emptyIconFile" accept="image/*"
                                            class="block w-full text-[10px] text-gray-600 dark:text-gray-300 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-[10px] file:font-semibold file:bg-indigo-50 dark:file:bg-indigo-900/40 file:text-indigo-700 dark:file:text-indigo-300">
                                        @if ($projectLive->empty_icon)
                                            <button type="button" wire:click="removeEmptyIconGlobal" wire:confirm="Kembalikan ke icon bawaan (+)?"
                                                class="flex-shrink-0 text-[10px] font-semibold text-gray-400 hover:text-red-500">
                                                Hapus
                                            </button>
                                        @endif
                                    </div>
                                    <div wire:loading wire:target="emptyIconFile" class="text-[10px] text-gray-400">Mengunggah...</div>
                                    <x-input-error :messages="$errors->get('emptyIconFile')" class="mt-1" />
                                </div>
                            @endif

                            @if ($field === 'empty_label')
                                <p class="text-[10px] text-gray-400">Teks lokal per-kotak diatur di Preview Live.</p>
                                <div class="border-t border-gray-100 dark:border-gray-700 pt-2 space-y-1">
                                    <x-input-label for="emptyLabelFont" value="Font (semua kotak)" class="text-[10px] font-semibold text-gray-500 dark:text-gray-400" />
                                    <select wire:model="emptyLabelFont" id="emptyLabelFont"
                                        class="block mt-0.5 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm text-xs">
                                        @foreach (\App\Enums\SeatFont::cases() as $option)
                                            <option value="{{ $option->value }}">{{ $option->label() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            @if ($field === 'host_name')
                                <p class="text-[10px] text-gray-400">Nama Host per kotak diatur di Preview Live.</p>
                                <div class="border-t border-gray-100 dark:border-gray-700 pt-2 space-y-1">
                                    <x-input-label for="hostNameFont" value="Font" class="text-[10px] font-semibold text-gray-500 dark:text-gray-400" />
                                    <select wire:model="hostNameFont" id="hostNameFont"
                                        class="block mt-0.5 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm text-xs">
                                        @foreach (\App\Enums\SeatFont::cases() as $option)
                                            <option value="{{ $option->value }}">{{ $option->label() }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Tulisan "Host" (badge, bukan Nama Host) - tidak punya kartu
                                     ELEMENT_GROUPS sendiri (stylingnya per-BG, bukan global), jadi
                                     tombol Tampil/Sembunyi GLOBAL-nya ditaruh di sini, berdampingan
                                     dgn Nama Host. --}}
                                <div class="border-t border-gray-100 dark:border-gray-700 pt-2">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-[10px] font-semibold text-gray-500 dark:text-gray-400">Tulisan "Host"</span>
                                        <button type="button" wire:click="toggleVisibilityDraft('host_badge')"
                                            class="flex-shrink-0 inline-flex items-center gap-1.5 rounded-full pl-1 pr-2 py-0.5 transition {{ ($visibility['host_badge'] ?? true) ? 'bg-green-600' : 'bg-gray-300 dark:bg-gray-600' }}">
                                            <span class="relative inline-flex h-4 w-7 items-center rounded-full bg-black/20">
                                                <span class="inline-block h-3 w-3 transform rounded-full bg-white transition {{ ($visibility['host_badge'] ?? true) ? 'translate-x-3.5' : 'translate-x-0.5' }}"></span>
                                            </span>
                                            <span class="text-[10px] font-semibold text-white">{{ ($visibility['host_badge'] ?? true) ? 'Tampil' : 'Sembunyi' }}</span>
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                {{-- Padding/border/jarak - level KOTAK (bukan per elemen visual spt di atas),
                     ikut tersimpan lewat tombol Simpan yang sama di paling bawah form ini
                     (satu $boxStyle array yang sama, lihat DetailAdmin::saveGlobalSettings()). --}}
                <div class="border-t border-gray-100 dark:border-gray-700 pt-3 space-y-3">
                    <p class="text-xs font-semibold text-gray-600 dark:text-gray-300">Padding, Border &amp; Jarak Kotak</p>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        @foreach (['seat_padding', 'seat_border_width', 'seat_border_radius', 'seat_gap'] as $field)
                            @php $config = \App\Livewire\ProjectLive\DetailAdmin::BOX_STYLE_FIELDS[$field]; @endphp
                            <div>
                                <x-input-label :for="'box-style-'.$field" :value="$config['label']" class="text-[10px]" />
                                <div class="flex items-center gap-1 mt-0.5">
                                    <x-text-input :id="'box-style-'.$field" wire:model="boxStyle.{{ $field }}" type="number" :min="$config['min']" :max="$config['max']" step="1" class="block w-full text-sm" />
                                    <span class="text-xs text-gray-400 flex-shrink-0">px</span>
                                </div>
                                <x-input-error :messages="$errors->get('boxStyle.'.$field)" class="mt-1" />
                            </div>
                        @endforeach
                    </div>

                    {{-- Warna GLOBAL border & BG kotak kosong - fallback kalau override LOKAL
                         kotak (tombol "Custom" per kotak di Preview Live) tidak diaktifkan.
                         Kosong = pakai perilaku default lama (border-white/15, #000000). --}}
                    <div class="border-t border-gray-100 dark:border-gray-700 pt-3 space-y-3">
                        <p class="text-xs font-semibold text-gray-600 dark:text-gray-300">Warna Kotak (Global)</p>
                        <p class="text-[10px] text-gray-400">Berlaku ke semua kotak, kecuali kotak yang di-custom lokal lewat tombol "Custom" di Preview Live.</p>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <x-input-label for="seatBorderColor" value="Warna Border" />
                                <div class="flex items-center gap-2 mt-1">
                                    <input type="color" wire:model="seatBorderColor" id="seatBorderColor"
                                        value="{{ $seatBorderColor ?: '#ffffff' }}"
                                        class="h-9 w-12 rounded-md border border-gray-200 dark:border-gray-600 cursor-pointer">
                                    <x-text-input wire:model="seatBorderColor" class="block w-full text-xs" type="text" placeholder="Default" />
                                    @if ($seatBorderColor)
                                        <button type="button" wire:click="$set('seatBorderColor', '')"
                                            class="flex-shrink-0 text-xs font-semibold text-gray-400 hover:text-red-500">
                                            Reset
                                        </button>
                                    @endif
                                </div>
                                <x-input-error :messages="$errors->get('seatBorderColor')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="seatEmptyBgColor" value="Warna BG Kotak Kosong" />
                                <div class="flex items-center gap-2 mt-1">
                                    <input type="color" wire:model="seatEmptyBgColor" id="seatEmptyBgColor"
                                        value="{{ $seatEmptyBgColor ?: '#000000' }}"
                                        class="h-9 w-12 rounded-md border border-gray-200 dark:border-gray-600 cursor-pointer">
                                    <x-text-input wire:model="seatEmptyBgColor" class="block w-full text-xs" type="text" placeholder="Default" />
                                    @if ($seatEmptyBgColor)
                                        <button type="button" wire:click="$set('seatEmptyBgColor', '')"
                                            class="flex-shrink-0 text-xs font-semibold text-gray-400 hover:text-red-500">
                                            Reset
                                        </button>
                                    @endif
                                </div>
                                <x-input-error :messages="$errors->get('seatEmptyBgColor')" class="mt-1" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                        class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded-md hover:bg-indigo-700">
                        Simpan
                    </button>
                </div>
                </form>

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
