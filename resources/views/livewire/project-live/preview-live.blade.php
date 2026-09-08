<div class="bg-black text-white min-h-screen overflow-hidden">
    {{-- Bar kontrol tipis mengambang di atas - halaman ini SENGAJA fullscreen tanpa
         header (layouts.live, sama seperti live-show.blade.php) supaya preview-nya
         benar-benar mirip tampilan Live asli, bar ini cuma overlay tipis di atasnya. --}}
    <div class="fixed top-0 inset-x-0 z-20 flex items-center justify-between gap-3 px-4 py-2 bg-black/70 backdrop-blur-sm text-xs">
        <a href="{{ route('project-live.admin', $projectLive) }}" wire:navigate
            class="text-indigo-400 hover:underline flex-shrink-0">&larr; Admin</a>
        <p class="text-gray-400 truncate">
            @if ($projectLive->auto_gift_mode)
                Otomatis dari gift TikTok LIVE - edit manual bisa ketiban update berikutnya.
            @else
                {{ $details->count() }} kursi - klik kotak utk edit.
            @endif
        </p>
        <div class="flex-shrink-0 flex items-center gap-2">
            <button wire:click="showAll" wire:confirm="Tampilkan semua kursi?" type="button"
                class="inline-flex items-center px-2.5 py-1 bg-green-600 text-white font-semibold rounded-md hover:bg-green-700">
                Show All
            </button>
            <button wire:click="hideAll" wire:confirm="Sembunyikan semua kursi?" type="button"
                class="inline-flex items-center px-2.5 py-1 bg-gray-700 text-white font-semibold rounded-md hover:bg-gray-600">
                Hide All
            </button>
        </div>
    </div>

    @php
        $mode = $projectLive->display_mode;
    @endphp
    {{-- Efek pulse kursi - SAMA PERSIS dgn live-show.blade.php, lihat komentar di
         sana kenapa (App\Livewire\ProjectLive\FrameHost, menu "Frame Host"). BUG
         YANG SUDAH KEJADIAN: partials/seat-box.blade.php nempelin
         "animation: gtt-seat-pulse ..." ke kotak yg posisinya tercentang, tapi
         @keyframes-nya SEBELUMNYA cuma didefinisikan di live-show.blade.php - di
         Preview Live animasinya nunjuk ke keyframe yang TIDAK ADA sama sekali,
         browser diam2 mengabaikannya (border cuma tampil warna statis lokal/global,
         TIDAK PERNAH nge-pulse) - jadi Preview Live kelihatan beda dari Live asli
         padahal rule Frame Host-nya harusnya menang duluan atas lokal/global. --}}
    @if ($projectLive->seat_pulse_enabled)
        <style>
            @keyframes gtt-seat-pulse {
                0% { border-color: {{ $projectLive->frame_pulse_color_1 }}; }
                @if ($projectLive->frame_pulse_color_3)
                    33% { border-color: {{ $projectLive->frame_pulse_color_2 }}; }
                    66% { border-color: {{ $projectLive->frame_pulse_color_3 }}; }
                @else
                    50% { border-color: {{ $projectLive->frame_pulse_color_2 }}; }
                @endif
                100% { border-color: {{ $projectLive->frame_pulse_color_1 }}; }
            }
        </style>
    @endif
    <div class="w-screen overflow-hidden flex items-start justify-center px-3 pt-20" style="height: 97vh;">
        {{-- Kotak mobile-first max 480px ini SATU-SATUNYA acuan ukuran/posisi baik utk
             grid kursi MAUPUN BG layar penuh - SAMA PERSIS dgn live-show.blade.php,
             lihat komentar di sana. --}}
        <div style="
            --seat-w: min(100vw, 93vh * {{ $mode->ratioW() }} / {{ $mode->ratioH() }}, 480px);
            width: var(--seat-w);
            height: {{ $mode->intrinsicHeight() ? 'auto' : 'calc(var(--seat-w) * '.$mode->ratioH().' / '.$mode->ratioW().')' }};
            position: relative;
        ">
            @if ($screenBackground)
                @php $screenFit = \App\Enums\BackgroundFit::from($screenBackground['fit_mode'])->cssObjectFit(); @endphp
                <div style="position: absolute; inset: 0; z-index: 0; overflow: hidden;">
                    @if ($screenBackground['type'] === 'video')
                        <video src="{{ $screenBackground['url'] }}" autoplay loop muted playsinline
                            style="width: 100%; height: 100%; object-fit: {{ $screenFit }}; transform: translate({{ $screenBackground['offset_x'] }}px, {{ $screenBackground['offset_y'] }}px) scale({{ $screenBackground['scale'] / 100 }});"></video>
                    @else
                        <img src="{{ $screenBackground['url'] }}" alt=""
                            style="width: 100%; height: 100%; object-fit: {{ $screenFit }}; transform: translate({{ $screenBackground['offset_x'] }}px, {{ $screenBackground['offset_y'] }}px) scale({{ $screenBackground['scale'] / 100 }});">
                    @endif
                </div>
            @endif

            <div class="grid" style="
                width: 100%;
                height: 100%;
                grid-template-columns: {{ $mode->gridTemplateColumns() }};
                grid-template-rows: {{ $mode->gridTemplateRows() }};
                gap: {{ $projectLive->seat_gap }}px;
                position: relative;
                z-index: 1;
                @if ($mode->gridTemplateAreas()) grid-template-areas: {{ $mode->gridTemplateAreas() }}; @endif
            ">
                @foreach ($details as $detail)
                    @php
                        $seatStyle = ($mode->gridTemplateAreas() ? 'grid-area: s'.$detail['position'].';' : '')
                            .($mode->seatStyleOverrides()[$detail['position']] ?? '')
                            .(($detail['border_color'] ?? null) ? ' border-color: '.$detail['border_color'].';' : '');
                    @endphp

                    @if (($detail['background']['role'] ?? 'none') === 'co_host')
                        {{-- Co-Host (App\Enums\SeatRole) - di Live asli medianya tampil PENUH
                             edge-to-edge (spt role Host, BUKAN avatar lingkaran lagi), jadi kartu
                             preview-nya disamakan juga: media BG jadi latar penuh kartu, nama/coin
                             dioverlay di bawah pakai gradient supaya tetap kebaca (ukuran teks
                             tetap established: nama text-[9px], coin text-[8px], badge posisi
                             text-[7px]). Klik tetap buka dialog role BG (openBgEdit), bukan
                             openEdit - nama/coin/mic kursi ini diedit lewat dialog itu. --}}
                        @php $coHostIsVideo = $detail['background']['type'] === 'video'; @endphp
                        <div wire:click="openBgEdit({{ $detail['id'] }})" role="button" tabindex="0"
                            style="{{ $seatStyle }} {{ $detail['background']['fit_mode'] === 'circle' ? 'background-color: '.$detail['background']['circle_bg_color'].';' : '' }}"
                            class="relative w-full h-full rounded-xl overflow-hidden border border-gray-700 hover:ring-2 hover:ring-indigo-500 transition cursor-pointer">
                            @if ($detail['background']['fit_mode'] === 'circle')
                                {{-- Lingkaran di tengah kartu (App\Enums\BackgroundFit::Circle) -
                                     samain dgn Live asli (partials/seat-box.blade.php), biar Preview
                                     tidak menyesatkan admin soal tampilan aslinya. --}}
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <div class="aspect-square rounded-full overflow-hidden" style="width: {{ $detail['background']['scale'] * 0.62 }}%;">
                                        @if ($coHostIsVideo)
                                            <video src="{{ $detail['background']['url'] }}" class="w-full h-full object-cover" muted playsinline></video>
                                        @else
                                            <img src="{{ $detail['background']['url'] }}" class="w-full h-full object-cover" alt="{{ $detail['name'] }}">
                                        @endif
                                    </div>
                                </div>
                            @elseif ($coHostIsVideo)
                                <video src="{{ $detail['background']['url'] }}" class="absolute inset-0 w-full h-full object-cover" muted playsinline></video>
                            @else
                                <img src="{{ $detail['background']['url'] }}" class="absolute inset-0 w-full h-full object-cover" alt="{{ $detail['name'] }}">
                            @endif

                            <span class="absolute top-1.5 left-1.5 text-[7px] font-semibold px-1 py-0.5 rounded bg-black/60 text-white">
                                #{{ $detail['position'] }} &middot; Co-Host
                            </span>

                            {{-- Tombol "Custom" - buka panel override LOKAL elemen visual kotak ini
                                 (coin/nama/mic/dst, App\Support\SeatStyleResolver), BEDA dari klik
                                 kartu (openBgEdit, DATA kursi ini) - stop propagation biar tidak
                                 ikut memicu openBgEdit. --}}
                            <button type="button" wire:click.stop="openStyleEdit({{ $detail['id'] }})"
                                title="Custom tampilan kotak ini"
                                class="absolute bottom-1.5 right-1.5 z-10 flex items-center justify-center w-4 h-4 rounded bg-black/60 text-white text-[9px] hover:bg-indigo-600">
                                ⚙
                            </button>

                            <div class="absolute inset-x-0 bottom-0 flex flex-col items-center gap-0 leading-tight py-1 bg-gradient-to-t from-black/80 to-transparent">
                                <span class="text-[9px] font-medium text-gray-100 truncate max-w-[90%]">
                                    {{ $detail['name'] ?: 'Belum diisi' }}
                                </span>

                                <span class="text-[8px] text-gray-300">
                                    {{ number_format($detail['gift_total_value']) }} coin
                                </span>
                            </div>
                        </div>
                    @elseif ($detail['background'] ?? null)
                        {{-- Kotak ini jadi BG custom (App\Livewire\ProjectLive\Background) - klik
                             buka dialog KHUSUS (openBgEdit, beda dari openEdit kursi normal) buat
                             atur role Host/Co-Host, lihat App\Enums\SeatRole. --}}
                        @php $seatFit = \App\Enums\BackgroundFit::from($detail['background']['fit_mode'])->cssObjectFit(); @endphp
                        <div wire:click="openBgEdit({{ $detail['id'] }})" role="button" tabindex="0"
                            style="{{ $seatStyle }} {{ $detail['background']['fit_mode'] === 'circle' ? 'background-color: '.$detail['background']['circle_bg_color'].';' : '' }}"
                            class="relative w-full h-full rounded-xl overflow-hidden border border-gray-700 hover:ring-2 hover:ring-indigo-500 transition cursor-pointer">
                            @if ($detail['background']['fit_mode'] === 'circle')
                                {{-- Lingkaran di tengah kartu - samain dgn Live asli, lihat komentar
                                     detail di partials/seat-box.blade.php. --}}
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <div class="aspect-square rounded-full overflow-hidden" style="width: {{ $detail['background']['scale'] * 0.62 }}%;">
                                        @if ($detail['background']['type'] === 'video')
                                            <video src="{{ $detail['background']['url'] }}" autoplay loop muted playsinline class="w-full h-full object-cover"></video>
                                        @else
                                            <img src="{{ $detail['background']['url'] }}" alt="" class="w-full h-full object-cover">
                                        @endif
                                    </div>
                                </div>
                            @elseif ($detail['background']['type'] === 'video')
                                <video src="{{ $detail['background']['url'] }}" autoplay loop muted playsinline
                                    style="width: 100%; height: 100%; object-fit: {{ $seatFit }}; transform: translate({{ $detail['background']['offset_x'] }}px, {{ $detail['background']['offset_y'] }}px) scale({{ $detail['background']['scale'] / 100 }});"></video>
                            @else
                                <img src="{{ $detail['background']['url'] }}" alt=""
                                    style="width: 100%; height: 100%; object-fit: {{ $seatFit }}; transform: translate({{ $detail['background']['offset_x'] }}px, {{ $detail['background']['offset_y'] }}px) scale({{ $detail['background']['scale'] / 100 }});">
                            @endif
                            <span class="absolute top-1.5 left-1.5 text-[10px] font-semibold px-1.5 py-0.5 rounded bg-black/60 text-white">
                                #{{ $detail['position'] }} &middot; BG
                                @if ($detail['background']['role'] === 'host')
                                    &middot; Host
                                @endif
                            </span>

                            <button type="button" wire:click.stop="openStyleEdit({{ $detail['id'] }})"
                                title="Custom tampilan kotak ini"
                                class="absolute bottom-1.5 right-1.5 z-10 flex items-center justify-center w-4 h-4 rounded bg-black/60 text-white text-[9px] hover:bg-indigo-600">
                                ⚙
                            </button>

                            {{-- Nama Host - kartu preview ikut nampilin begitu sudah diisi lewat
                                 modal openBgEdit(), biar admin bisa cek tanpa buka Live asli. --}}
                            @if ($detail['background']['role'] === 'host' && $detail['name'])
                                <div class="absolute inset-x-0 bottom-0 flex items-center px-1.5 py-1 bg-gradient-to-t from-black/80 to-transparent">
                                    <span class="text-[9px] font-bold text-gray-100 truncate max-w-[90%]">
                                        {{ $detail['name'] }}
                                    </span>
                                </div>
                            @endif
                        </div>
                    @else
                        <div wire:click="openEdit({{ $detail['id'] }})" role="button" tabindex="0"
                            style="{{ $seatStyle }}"
                            class="relative w-full h-full rounded-xl overflow-hidden bg-gray-900 flex flex-col items-center justify-center gap-1 hover:ring-2 hover:ring-indigo-500 transition cursor-pointer {{ $detail['is_pinned'] ? 'border-2 border-amber-500' : 'border border-gray-700' }}">
                            {{-- Pin (App\Services\GiftLeaderboardService) - datanya dikunci, tidak ikut
                                 ter-reset/ditimpa auto-gift selama pin aktif. Border kotak & badge ini
                                 SENGAJA lebih mencolok (amber) drpd indikator lain, biar status "aktif
                                 dikunci" langsung kelihatan sekilas tanpa perlu buka modal. --}}
                            @if ($detail['is_pinned'])
                                <span class="absolute bottom-1.5 left-1.5 z-10 flex items-center gap-0.5 text-[8px] font-semibold px-1 py-0.5 rounded-full bg-amber-500 text-black shadow" title="Di-pin - tidak ikut reset/auto-gift">
                                    📌 PIN
                                </span>
                            @endif
                            <span class="absolute top-1.5 left-1.5 flex items-center gap-1">
                                <span class="text-[7px] font-semibold px-1 py-0.5 rounded bg-black/60 text-white">
                                    #{{ $detail['position'] }}
                                </span>
                                @php
                                    $previewColor = $detail['status'] === 'show' ? $detail['dominant_color'] : ($detail['active_hotkey_color'] ?: '#000000');
                                @endphp
                                <span class="w-3 h-3 rounded-full border border-white/60 shadow" style="background: {{ $previewColor }};" title="{{ $previewColor }}"></span>
                            </span>

                            <!-- Toggle status: klik langsung ubah tanpa buka modal -->
                            <button type="button" wire:click.stop="toggleStatus({{ $detail['id'] }})"
                                title="{{ $detail['status'] === 'show' ? 'Klik untuk Hide' : 'Klik untuk Show' }}"
                                class="absolute top-1.5 right-1.5 flex items-center gap-0.5 rounded-full px-0.5 py-0.5 transition {{ $detail['status'] === 'show' ? 'bg-green-600' : 'bg-gray-600' }}">
                                <span class="relative inline-flex h-2.5 w-4 items-center rounded-full bg-black/20">
                                    <span class="inline-block h-1.5 w-1.5 transform rounded-full bg-white transition {{ $detail['status'] === 'show' ? 'translate-x-1.5' : 'translate-x-0.5' }}"></span>
                                </span>
                                <span class="text-[7px] font-semibold text-white pr-0.5">{{ $detail['status'] === 'show' ? 'Show' : 'Hide' }}</span>
                            </button>

                            @if ($detail['img_url'])
                                <img src="{{ $detail['img_url'] }}" class="w-12 h-12 rounded-full object-cover" alt="{{ $detail['name'] }}">
                            @else
                                <div class="w-12 h-12 rounded-full bg-gray-700 flex items-center justify-center text-gray-400 text-lg">
                                    +
                                </div>
                            @endif

                            <div class="flex flex-col items-center gap-0 leading-tight mt-1">
                                <span class="text-[9px] font-medium text-gray-300 truncate max-w-[90%]">
                                    {{ $detail['name'] ?: 'Belum diisi' }}
                                </span>

                                <span class="text-[8px] text-gray-500">
                                    {{ number_format($detail['gift_total_value']) }} coin
                                </span>
                            </div>

                            @if ($detail['hotkey'])
                                <span class="absolute bottom-1.5 right-6 text-[10px] font-mono px-1.5 py-0.5 rounded bg-indigo-600 text-white">
                                    {{ $detail['hotkey'] }}
                                </span>
                            @endif

                            <button type="button" wire:click.stop="openStyleEdit({{ $detail['id'] }})"
                                title="Custom tampilan kotak ini"
                                class="absolute bottom-1.5 right-1.5 z-10 flex items-center justify-center w-4 h-4 rounded bg-black/60 text-white text-[9px] hover:bg-indigo-600">
                                ⚙
                            </button>
                        </div>
                    @endif
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
                class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6 space-y-4 text-gray-900 dark:text-gray-100">
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
                        <x-input-label for="coin" value="Coin" />
                        <x-text-input wire:model="coin" id="coin" class="block mt-1 w-full" type="number" min="0" placeholder="0" />
                        <p class="text-xs text-gray-400 mt-1">Angka gift/coin yang tampil di badge kursi ini.</p>
                        <x-input-error :messages="$errors->get('coin')" class="mt-2" />
                    </div>

                    <p class="text-[10px] text-gray-400">
                        Tampilan kotak kosong (teks/font/icon) & warna border diatur lewat tombol &quot;Custom&quot; di kartu kotak ini.
                    </p>

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

                    <p class="text-[10px] text-gray-400">Tampil/sembunyi, ukuran/posisi & icon mic kotak ini diatur lewat tombol &quot;Custom&quot; di kartu kotak ini.</p>

                    <div class="border-t border-gray-100 dark:border-gray-700 pt-4">
                        <x-input-label value="Pin Kursi" />
                        <button type="button" wire:click="toggleModalPinned"
                            class="mt-1 inline-flex items-center gap-2 rounded-full pl-1 pr-3 py-1 transition {{ $isPinned ? 'bg-amber-500' : 'bg-gray-300 dark:bg-gray-600' }}">
                            <span class="relative inline-flex h-6 w-11 items-center rounded-full bg-black/20">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition {{ $isPinned ? 'translate-x-6' : 'translate-x-1' }}"></span>
                            </span>
                            <span class="text-sm font-medium text-white">{{ $isPinned ? 'Di-pin 📌' : 'Tidak di-pin' }}</span>
                        </button>
                        <p class="text-xs text-gray-400 mt-1">
                            Kalau di-pin, nama/foto/coin kursi ini TIDAK ikut ke-reset oleh Reset Leaderboard/Reset Coin, dan tidak akan ditimpa gifter baru dari auto-gift. Cocok utk kursi sponsor/tamu tetap.
                        </p>
                        <x-input-error :messages="$errors->get('isPinned')" class="mt-2" />
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

    <!-- Modal Edit Kotak BG (App\Enums\SeatRole) - beda dari modal kursi normal di atas,
         dipicu wire:click="openBgEdit(...)" dari kotak yang background_id-nya terisi. -->
    <div x-show="$wire.editingBgDetailId !== null" x-cloak
        class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div x-show="$wire.editingBgDetailId !== null" x-transition.opacity wire:click="closeBgEdit"
                class="fixed inset-0 bg-black/60"></div>

            <div x-show="$wire.editingBgDetailId !== null" x-transition
                class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6 space-y-4 text-gray-900 dark:text-gray-100">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    Edit Kotak BG
                </h3>

                <form wire:submit="saveBgEdit" class="space-y-4">
                    <div>
                        <x-input-label value="Peran Kotak" />
                        <div class="grid grid-cols-1 gap-1.5 mt-1">
                            @foreach (\App\Enums\SeatRole::cases() as $option)
                                <button type="button" wire:click="$set('bgRole', '{{ $option->value }}')"
                                    class="text-left px-3 py-2 rounded-md border text-xs font-medium transition {{ $bgRole === $option->value ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300' : 'border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                                    {{ $option->label() }}
                                </button>
                            @endforeach
                        </div>
                        <x-input-error :messages="$errors->get('bgRole')" class="mt-2" />
                    </div>

                    @if ($bgRole === 'host')
                        <div class="border-t border-gray-100 dark:border-gray-700 pt-4 space-y-3">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400">Nama Host</p>
                                {{-- Tampil/Sembunyi LOKAL kotak ini - kosong (Global) = ikut default
                                     Admin (project_lives.host_name_visible). --}}
                                <button type="button" wire:click="toggleHostNameVisible"
                                    class="flex-shrink-0 inline-flex items-center gap-1.5 rounded-full pl-1 pr-2 py-0.5 transition {{ $hostNameVisible !== '' ? 'bg-green-600' : 'bg-gray-300 dark:bg-gray-600' }}">
                                    <span class="relative inline-flex h-4 w-7 items-center rounded-full bg-black/20">
                                        <span class="inline-block h-3 w-3 transform rounded-full bg-white transition {{ $hostNameVisible !== '' ? 'translate-x-3.5' : 'translate-x-0.5' }}"></span>
                                    </span>
                                    <span class="text-[10px] font-semibold text-white">{{ $hostNameVisible !== '' ? 'Lokal' : 'Global' }}</span>
                                </button>
                            </div>
                            @if ($hostNameVisible !== '')
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-[10px] text-gray-500 dark:text-gray-400">Tampil/Sembunyi</span>
                                    <button type="button" wire:click="$set('hostNameVisible', '{{ $hostNameVisible === '1' ? '0' : '1' }}')"
                                        class="flex-shrink-0 inline-flex items-center gap-1.5 rounded-full pl-1 pr-2 py-0.5 transition {{ $hostNameVisible === '1' ? 'bg-green-600' : 'bg-gray-300 dark:bg-gray-600' }}">
                                        <span class="relative inline-flex h-4 w-7 items-center rounded-full bg-black/20">
                                            <span class="inline-block h-3 w-3 transform rounded-full bg-white transition {{ $hostNameVisible === '1' ? 'translate-x-3.5' : 'translate-x-0.5' }}"></span>
                                        </span>
                                        <span class="text-[10px] font-semibold text-white">{{ $hostNameVisible === '1' ? 'Tampil' : 'Sembunyi' }}</span>
                                    </button>
                                </div>
                            @endif
                            <p class="text-[10px] text-gray-400">
                                Tampil bold di pojok kiri bawah kotak (tanpa badge). Font/ukuran/posisi
                                diatur global di halaman
                                <a href="{{ route('project-live.admin', $projectLive) }}" wire:navigate class="text-indigo-600 dark:text-indigo-400 hover:underline">Admin</a>.
                            </p>

                            <div>
                                <span class="inline-block font-bold text-white" style="text-shadow: 0 1px 3px rgba(0,0,0,.8);">
                                    {{ $name !== '' ? $name : 'Nama Host' }}
                                </span>
                            </div>

                            <div>
                                <x-input-label for="hostName" value="Nama" />
                                <x-text-input wire:model="name" id="hostName" class="block mt-1 w-full" type="text" placeholder="mis. Opa Mauro" />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>
                        </div>

                        <div class="border-t border-gray-100 dark:border-gray-700 pt-4 space-y-3">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400">Style Badge "Host"</p>
                                <button type="button" wire:click="toggleHostBadgeVisible"
                                    class="flex-shrink-0 inline-flex items-center gap-1.5 rounded-full pl-1 pr-2 py-0.5 transition {{ $hostBadgeVisible !== '' ? 'bg-green-600' : 'bg-gray-300 dark:bg-gray-600' }}">
                                    <span class="relative inline-flex h-4 w-7 items-center rounded-full bg-black/20">
                                        <span class="inline-block h-3 w-3 transform rounded-full bg-white transition {{ $hostBadgeVisible !== '' ? 'translate-x-3.5' : 'translate-x-0.5' }}"></span>
                                    </span>
                                    <span class="text-[10px] font-semibold text-white">{{ $hostBadgeVisible !== '' ? 'Lokal' : 'Global' }}</span>
                                </button>
                            </div>
                            @if ($hostBadgeVisible !== '')
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-[10px] text-gray-500 dark:text-gray-400">Tampil/Sembunyi</span>
                                    <button type="button" wire:click="$set('hostBadgeVisible', '{{ $hostBadgeVisible === '1' ? '0' : '1' }}')"
                                        class="flex-shrink-0 inline-flex items-center gap-1.5 rounded-full pl-1 pr-2 py-0.5 transition {{ $hostBadgeVisible === '1' ? 'bg-green-600' : 'bg-gray-300 dark:bg-gray-600' }}">
                                        <span class="relative inline-flex h-4 w-7 items-center rounded-full bg-black/20">
                                            <span class="inline-block h-3 w-3 transform rounded-full bg-white transition {{ $hostBadgeVisible === '1' ? 'translate-x-3.5' : 'translate-x-0.5' }}"></span>
                                        </span>
                                        <span class="text-[10px] font-semibold text-white">{{ $hostBadgeVisible === '1' ? 'Tampil' : 'Sembunyi' }}</span>
                                    </button>
                                </div>
                            @endif

                            <div class="flex items-center justify-center py-2">
                                {{-- Preview live pakai nilai yang lagi di-staging (belum Simpan) -
                                     teks/font/posisi ambil dari properti bundel Tulisan Host di
                                     bawah kalau lagi Lokal (bisa null saat Global, makanya ada
                                     fallback), warna/ukuran tetap dari properti di section ini.
                                     Logo sama pola: URL LOKAL yang lagi di-staging kalau ada,
                                     atau GLOBAL kalau tidak - visible-nya LOKAL kalau section
                                     "Logo di Samping Tulisan Host" lagi di-toggle Lokal, atau
                                     GLOBAL kalau tidak. --}}
                                @php
                                    $previewLogoUrl = $this->hostBadgeLogoUrl() ?: $projectLive->hostBadgeLogoUrl();
                                    $previewLogoVisible = $hostBadgeLogoVisible !== '' ? $hostBadgeLogoVisible === '1' : $projectLive->host_badge_logo_visible;
                                @endphp
                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1"
                                    style="background: {{ $hostBadgeBgColor }}; transform: translate({{ $hostBadgeOffsetX ?? 0 }}px, {{ $hostBadgeOffsetY ?? 0 }}px) scale({{ $hostBadgeSize / 100 }});">
                                    @if ($previewLogoVisible && $previewLogoUrl)
                                        <img src="{{ $previewLogoUrl }}" alt="" class="w-4 h-4 rounded-full object-cover flex-shrink-0">
                                    @endif
                                    <span style="color: {{ $hostBadgeTextColor }};" class="text-sm font-semibold">{{ $hostBadgeText ?: 'Host' }}</span>
                                </span>
                            </div>

                            {{-- Preset MASTER (App\Models\HostBadgePreset, database/seeders/
                                 HostBadgePresetSeeder.php) - klik cuma ngisi warna/font di
                                 bawah, BUKAN nge-lock, tetap bisa diubah manual sesudahnya. --}}
                            @if ($hostBadgePresets->isNotEmpty())
                                <div>
                                    <x-input-label value="Preset" />
                                    <div class="flex flex-wrap gap-1.5 mt-1">
                                        @foreach ($hostBadgePresets as $preset)
                                            <button type="button" wire:click="applyHostBadgePreset({{ $preset->id }})"
                                                title="{{ $preset->name }}"
                                                class="inline-flex items-center rounded-full px-2.5 py-1 border border-gray-200 dark:border-gray-600 hover:ring-2 hover:ring-indigo-500 transition"
                                                style="background: {{ $preset->bg_color }};">
                                                <span class="text-xs font-semibold" style="color: {{ $preset->text_color }};">{{ $preset->name }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <x-input-label for="hostBadgeBgColor" value="Warna Latar" />
                                    <div class="flex items-center gap-2 mt-1">
                                        <input type="color" wire:model="hostBadgeBgColor" id="hostBadgeBgColor"
                                            class="h-9 w-12 rounded-md border border-gray-200 dark:border-gray-600 cursor-pointer">
                                        <x-text-input wire:model="hostBadgeBgColor" class="block w-full text-xs" type="text" />
                                    </div>
                                    <x-input-error :messages="$errors->get('hostBadgeBgColor')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="hostBadgeTextColor" value="Warna Teks" />
                                    <div class="flex items-center gap-2 mt-1">
                                        <input type="color" wire:model="hostBadgeTextColor" id="hostBadgeTextColor"
                                            class="h-9 w-12 rounded-md border border-gray-200 dark:border-gray-600 cursor-pointer">
                                        <x-text-input wire:model="hostBadgeTextColor" class="block w-full text-xs" type="text" />
                                    </div>
                                    <x-input-error :messages="$errors->get('hostBadgeTextColor')" class="mt-1" />
                                </div>
                            </div>

                            <div>
                                <x-input-label for="hostBadgeSize" value="Ukuran Badge (%)" />
                                <x-text-input wire:model="hostBadgeSize" id="hostBadgeSize" class="block mt-1 w-full" type="number" min="50" max="200" />
                                <x-input-error :messages="$errors->get('hostBadgeSize')" class="mt-2" />
                            </div>
                        </div>

                        {{-- Teks/font/posisi tulisan "Host" - satu bundel toggle Lokal/Global
                             (App\Livewire\ProjectLive\PreviewLive::toggleHostBadgeCustom()),
                             TERPISAH dari warna/ukuran di atas yang selalu per-BG (tidak ada
                             versi global). --}}
                        <div class="border-t border-gray-100 dark:border-gray-700 pt-4 space-y-3">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400">Teks, Font &amp; Posisi Tulisan "Host"</p>
                                <button type="button" wire:click="toggleHostBadgeCustom"
                                    class="flex-shrink-0 inline-flex items-center gap-1.5 rounded-full pl-1 pr-2 py-0.5 transition {{ $hostBadgeOffsetX !== null ? 'bg-green-600' : 'bg-gray-300 dark:bg-gray-600' }}">
                                    <span class="relative inline-flex h-4 w-7 items-center rounded-full bg-black/20">
                                        <span class="inline-block h-3 w-3 transform rounded-full bg-white transition {{ $hostBadgeOffsetX !== null ? 'translate-x-3.5' : 'translate-x-0.5' }}"></span>
                                    </span>
                                    <span class="text-[10px] font-semibold text-white">{{ $hostBadgeOffsetX !== null ? 'Lokal' : 'Global' }}</span>
                                </button>
                            </div>

                            @if ($hostBadgeOffsetX !== null)
                                <div>
                                    <x-input-label for="hostBadgeTextInput" value="Teks (kosongkan = &quot;Host&quot;)" />
                                    <x-text-input wire:model="hostBadgeText" id="hostBadgeTextInput" class="block mt-1 w-full" type="text" placeholder="Host" maxlength="50" />
                                    <x-input-error :messages="$errors->get('hostBadgeText')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label value="Font" />
                                    <div class="grid grid-cols-2 gap-1.5 mt-1">
                                        @foreach (\App\Enums\SeatFont::cases() as $option)
                                            <button type="button" wire:click="$set('hostBadgeFont', '{{ $option->value }}')"
                                                style="font-family: {{ $option->cssFontFamily() }};"
                                                class="px-2 py-1.5 text-sm rounded-md border transition {{ $hostBadgeFont === $option->value ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300' : 'border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                                                {{ $option->label() }}
                                            </button>
                                        @endforeach
                                    </div>
                                    <x-input-error :messages="$errors->get('hostBadgeFont')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label value="Posisi Badge" />
                                    <div class="grid grid-cols-2 gap-3 mt-1">
                                        <div>
                                            <x-text-input wire:model="hostBadgeOffsetX" class="block w-full text-sm" type="number" min="-100" max="100" placeholder="Kiri/Kanan" />
                                            <x-input-error :messages="$errors->get('hostBadgeOffsetX')" class="mt-1" />
                                        </div>
                                        <div>
                                            <x-text-input wire:model="hostBadgeOffsetY" class="block w-full text-sm" type="number" min="-100" max="100" placeholder="Naik/Turun" />
                                            <x-input-error :messages="$errors->get('hostBadgeOffsetY')" class="mt-1" />
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Logo di samping tulisan "Host" - LOKAL kotak BG ini
                             (project_live_backgrounds.host_badge_logo) kalau di-upload, atau
                             ikut GLOBAL (App\Models\ProjectLive::hostBadgeLogoUrl(), diatur di
                             Admin) kalau tidak - toggle Lokal/Global di sini CUMA ngatur
                             visible-nya (project_live_backgrounds.host_badge_logo_visible,
                             sama pola dgn "Style Badge Host" di atas), sementara FILE lokalnya
                             sendiri otomatis aktif begitu diupload (tombol "Hapus" balikin ke
                             ikut Global lagi). --}}
                        <div class="border-t border-gray-100 dark:border-gray-700 pt-4 space-y-3">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400">Logo di Samping Tulisan "Host"</p>
                                <button type="button" wire:click="toggleHostBadgeLogoVisible"
                                    class="flex-shrink-0 inline-flex items-center gap-1.5 rounded-full pl-1 pr-2 py-0.5 transition {{ $hostBadgeLogoVisible !== '' ? 'bg-green-600' : 'bg-gray-300 dark:bg-gray-600' }}">
                                    <span class="relative inline-flex h-4 w-7 items-center rounded-full bg-black/20">
                                        <span class="inline-block h-3 w-3 transform rounded-full bg-white transition {{ $hostBadgeLogoVisible !== '' ? 'translate-x-3.5' : 'translate-x-0.5' }}"></span>
                                    </span>
                                    <span class="text-[10px] font-semibold text-white">{{ $hostBadgeLogoVisible !== '' ? 'Lokal' : 'Global' }}</span>
                                </button>
                            </div>
                            @if ($hostBadgeLogoVisible !== '')
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-[10px] text-gray-500 dark:text-gray-400">Tampil/Sembunyi</span>
                                    <button type="button" wire:click="$set('hostBadgeLogoVisible', '{{ $hostBadgeLogoVisible === '1' ? '0' : '1' }}')"
                                        class="flex-shrink-0 inline-flex items-center gap-1.5 rounded-full pl-1 pr-2 py-0.5 transition {{ $hostBadgeLogoVisible === '1' ? 'bg-green-600' : 'bg-gray-300 dark:bg-gray-600' }}">
                                        <span class="relative inline-flex h-4 w-7 items-center rounded-full bg-black/20">
                                            <span class="inline-block h-3 w-3 transform rounded-full bg-white transition {{ $hostBadgeLogoVisible === '1' ? 'translate-x-3.5' : 'translate-x-0.5' }}"></span>
                                        </span>
                                        <span class="text-[10px] font-semibold text-white">{{ $hostBadgeLogoVisible === '1' ? 'Tampil' : 'Sembunyi' }}</span>
                                    </button>
                                </div>
                            @endif

                            <div class="flex items-center gap-1.5">
                                @if ($this->hostBadgeLogoUrl())
                                    <img src="{{ $this->hostBadgeLogoUrl() }}" alt="" class="w-6 h-6 rounded-full object-cover flex-shrink-0">
                                @endif
                                <input type="file" wire:model="hostBadgeLogoFile" accept="image/*"
                                    class="block w-full text-[10px] text-gray-600 dark:text-gray-300 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-[10px] file:font-semibold file:bg-indigo-50 dark:file:bg-indigo-900/40 file:text-indigo-700 dark:file:text-indigo-300">
                                @if ($hostBadgeLogo !== '')
                                    <button type="button" wire:click="removeHostBadgeLogoLocal" wire:confirm="Hapus logo LOKAL kotak ini? (balik ikut Global)"
                                        class="flex-shrink-0 text-[10px] font-semibold text-gray-400 hover:text-red-500">
                                        Hapus
                                    </button>
                                @endif
                            </div>
                            <div wire:loading wire:target="hostBadgeLogoFile" class="text-[10px] text-gray-400">Mengunggah...</div>
                            <x-input-error :messages="$errors->get('hostBadgeLogoFile')" class="mt-1" />
                            <p class="text-[10px] text-gray-400">Kosong = ikut logo Global (diatur di Admin).</p>
                        </div>
                    @elseif ($bgRole === 'co_host')
                        <div class="border-t border-gray-100 dark:border-gray-700 pt-4 space-y-3">
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400">Kotak ini akan tampil seperti kursi normal (nama, coin, mic) di atas media BG-nya.</p>

                            <div>
                                <x-input-label for="coHostName" value="Nama" />
                                <x-text-input wire:model="name" id="coHostName" class="block mt-1 w-full" type="text" />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="coHostCoin" value="Coin" />
                                <x-text-input wire:model="coin" id="coHostCoin" class="block mt-1 w-full" type="number" min="0" placeholder="0" />
                                <x-input-error :messages="$errors->get('coin')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label value="Icon Mic" />
                                <button type="button" wire:click="toggleModalMic"
                                    class="mt-1 inline-flex items-center gap-2 rounded-full pl-1 pr-3 py-1 transition {{ $micEnabled ? 'bg-green-600' : 'bg-gray-300 dark:bg-gray-600' }}">
                                    <span class="relative inline-flex h-6 w-11 items-center rounded-full bg-black/20">
                                        <span class="inline-block h-4 w-4 transform rounded-full bg-white transition {{ $micEnabled ? 'translate-x-6' : 'translate-x-1' }}"></span>
                                    </span>
                                    <span class="text-sm font-medium text-white">{{ $micEnabled ? 'Tampil' : 'Sembunyi' }}</span>
                                </button>
                                <p class="text-[10px] text-gray-400 mt-1">Ukuran/posisi/icon mic (dan elemen lain) kotak ini bisa di-custom lewat tombol &quot;Custom&quot; di kartu kotak ini.</p>
                                <x-input-error :messages="$errors->get('micEnabled')" class="mt-2" />
                            </div>
                        </div>
                    @endif

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="closeBgEdit"
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

    {{-- Modal "Custom" - override LOKAL beberapa elemen visual KOTAK INI SAJA (beda
         dari 2 modal di atas yang isinya DATA - nama/foto/coin/role/dst). Tiap
         elemen (App\Livewire\ProjectLive\PreviewLive::STYLE_ELEMENTS) punya toggle
         Aktif/Nonaktif sendiri: Aktif = pakai size/geser di sini, Nonaktif = pakai
         settingan GLOBAL project_lives apa adanya (Admin -> Ukuran Konten Kotak
         Live). Dipicu tombol "⚙" di tiap kartu kotak di atas, TERLEPAS dari
         role/isi kotaknya (co-host/BG/normal/kosong sama2 bisa di-custom). --}}
    <div x-show="$wire.editingStyleDetailId !== null" x-cloak
        class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div x-show="$wire.editingStyleDetailId !== null" x-transition.opacity wire:click="closeStyleEdit"
                class="fixed inset-0 bg-black/60"></div>

            <div x-show="$wire.editingStyleDetailId !== null" x-transition
                class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-3xl w-full p-6 space-y-4 text-gray-900 dark:text-gray-100">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    Custom Kotak Ini
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 -mt-2">
                    Aktifkan elemen yang mau di-custom KHUSUS kotak ini - sisanya tetap ikut settingan global.
                </p>

                <form wire:submit="saveStyleEdit" class="space-y-3">
                    {{-- grid 2 kolom (bukan ditumpuk vertikal spt sebelumnya, tapi juga jangan
                         3 kolom - kepanjangan/kelewat lebar) - modal-nya dilebarkan secukupnya
                         (max-w-3xl) biar 2 kartu muat berdampingan tanpa jadi kelewat lebar. --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach (\App\Livewire\ProjectLive\PreviewLive::STYLE_ELEMENTS as $key => $config)
                            <div class="border border-gray-200 dark:border-gray-700 rounded-md p-3 space-y-2">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ $config['label'] }}</span>
                                    <button type="button" wire:click="toggleStyleElement('{{ $key }}')"
                                        class="flex-shrink-0 inline-flex items-center gap-1.5 rounded-full pl-1 pr-2 py-0.5 transition {{ ($styleOverrides[$key]['enabled'] ?? false) ? 'bg-green-600' : 'bg-gray-300 dark:bg-gray-600' }}">
                                        <span class="relative inline-flex h-4 w-7 items-center rounded-full bg-black/20">
                                            <span class="inline-block h-3 w-3 transform rounded-full bg-white transition {{ ($styleOverrides[$key]['enabled'] ?? false) ? 'translate-x-3.5' : 'translate-x-0.5' }}"></span>
                                        </span>
                                        <span class="text-[10px] font-semibold text-white">{{ ($styleOverrides[$key]['enabled'] ?? false) ? 'Lokal' : 'Global' }}</span>
                                    </button>
                                </div>

                                @if ($styleOverrides[$key]['enabled'] ?? false)
                                    {{-- Tampil/Sembunyi icon mic - PINDAH dari modal Edit Kursi ke sini
                                         (kartu "Icon Mic"), DI DALAM blok Lokal (bagian dari skema
                                         lokal panel ini, bukan settingan terpisah di luarnya). --}}
                                    @if ($key === 'mic')
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="text-[10px] text-gray-500 dark:text-gray-400">Tampil/Sembunyi</span>
                                            <button type="button" wire:click="toggleModalMic"
                                                class="flex-shrink-0 inline-flex items-center gap-1.5 rounded-full pl-1 pr-2 py-0.5 transition {{ $micEnabled ? 'bg-green-600' : 'bg-gray-300 dark:bg-gray-600' }}">
                                                <span class="relative inline-flex h-4 w-7 items-center rounded-full bg-black/20">
                                                    <span class="inline-block h-3 w-3 transform rounded-full bg-white transition {{ $micEnabled ? 'translate-x-3.5' : 'translate-x-0.5' }}"></span>
                                                </span>
                                                <span class="text-[10px] font-semibold text-white">{{ $micEnabled ? 'Tampil' : 'Sembunyi' }}</span>
                                            </button>
                                        </div>
                                    @endif

                                    {{-- Tampil/Sembunyi elemen ini KOTAK INI SAJA - DI DALAM blok
                                         Lokal (sama pola dgn mic di atas), cuma dipakai elemen yang
                                         punya 'visible_col' (ada pasangan GLOBAL-nya di project_lives). --}}
                                    @if (! empty($config['visible_col']))
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="text-[10px] text-gray-500 dark:text-gray-400">Tampil/Sembunyi</span>
                                            <button type="button" wire:click="toggleStyleElementVisible('{{ $key }}')"
                                                class="flex-shrink-0 inline-flex items-center gap-1.5 rounded-full pl-1 pr-2 py-0.5 transition {{ ($styleOverrides[$key]['visible'] ?? true) ? 'bg-green-600' : 'bg-gray-300 dark:bg-gray-600' }}">
                                                <span class="relative inline-flex h-4 w-7 items-center rounded-full bg-black/20">
                                                    <span class="inline-block h-3 w-3 transform rounded-full bg-white transition {{ ($styleOverrides[$key]['visible'] ?? true) ? 'translate-x-3.5' : 'translate-x-0.5' }}"></span>
                                                </span>
                                                <span class="text-[10px] font-semibold text-white">{{ ($styleOverrides[$key]['visible'] ?? true) ? 'Tampil' : 'Sembunyi' }}</span>
                                            </button>
                                        </div>
                                    @endif

                                    {{-- 'no_offset_x' (avatar doang) - foto user di tengah kotak cuma
                                         butuh ukuran & naik/turun, tanpa geser kiri/kanan. offset_x
                                         TETAP tersimpan di style_overrides apa adanya (default 0, lihat
                                         PreviewLive::saveStyleEdit()), cuma tidak ditampilkan di sini. --}}
                                    <div class="grid {{ ! empty($config['no_offset_x']) ? 'grid-cols-2' : 'grid-cols-3' }} gap-2">
                                        <div>
                                            <x-input-label value="Ukuran (%)" class="text-[10px]" />
                                            <x-text-input wire:model="styleOverrides.{{ $key }}.size" type="number" min="50" max="200" class="block w-full text-sm mt-0.5" />
                                        </div>
                                        @if (empty($config['no_offset_x']))
                                            <div>
                                                <x-input-label value="Kiri/Kanan" class="text-[10px]" />
                                                <x-text-input wire:model="styleOverrides.{{ $key }}.offset_x" type="number" min="-100" max="100" class="block w-full text-sm mt-0.5" />
                                            </div>
                                        @endif
                                        <div>
                                            <x-input-label value="Naik/Turun" class="text-[10px]" />
                                            <x-text-input wire:model="styleOverrides.{{ $key }}.offset_y" type="number" min="-100" max="100" class="block w-full text-sm mt-0.5" />
                                        </div>
                                    </div>
                                    <x-input-error :messages="$errors->get('styleOverrides.'.$key.'.size')" class="mt-1" />
                                    <x-input-error :messages="$errors->get('styleOverrides.'.$key.'.offset_x')" class="mt-1" />
                                    <x-input-error :messages="$errors->get('styleOverrides.'.$key.'.offset_y')" class="mt-1" />

                                    @if (! empty($config['has_icon']))
                                        {{-- Icon mic LOKAL (mic doang) - opsional, kosongkan tetap pakai icon
                                             mic GLOBAL (App\Models\ProjectLive::micIconUrl()). Bagian dari
                                             skema lokal (toggle Aktif di atas), jadi tetap di DALAM blok ini. --}}
                                        <div class="pt-1">
                                            <x-input-label value="Icon Mic Kotak Ini (opsional)" class="text-[10px]" />
                                            <div class="flex items-center gap-2 mt-1">
                                                @if ($styleOverrides[$key]['icon'] ?? null)
                                                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($styleOverrides[$key]['icon']) }}" class="w-7 h-7 object-contain rounded border border-gray-200 dark:border-gray-600 flex-shrink-0">
                                                @endif
                                                <input type="file" wire:model="localMicIconFile" accept="image/*"
                                                    class="block w-full text-xs text-gray-600 dark:text-gray-300 file:mr-2 file:py-1 file:px-2 file:rounded-md file:border-0 file:text-[10px] file:font-semibold file:bg-indigo-50 dark:file:bg-indigo-900/40 file:text-indigo-700 dark:file:text-indigo-300">
                                                @if ($styleOverrides[$key]['icon'] ?? null)
                                                    <button type="button" wire:click="removeLocalMicIcon"
                                                        class="flex-shrink-0 text-[10px] font-semibold text-gray-400 hover:text-red-500">
                                                        Hapus
                                                    </button>
                                                @endif
                                            </div>
                                            <div wire:loading wire:target="localMicIconFile" class="text-[10px] text-gray-400 mt-1">Mengunggah...</div>
                                            <x-input-error :messages="$errors->get('localMicIconFile')" class="mt-1" />
                                        </div>
                                    @endif

                                    {{-- Teks/font kotak kosong - PINDAH dari modal Edit Kursi ke sini,
                                         DI DALAM blok "enabled" (bagian dari skema lokal panel ini,
                                         bukan settingan terpisah di luarnya). --}}
                                    @if ($key === 'empty_label')
                                        <div class="pt-1 space-y-2">
                                            <div>
                                                <x-input-label for="emptyLabel" value="Teks" class="text-[10px]" />
                                                <x-text-input wire:model="emptyLabel" id="emptyLabel" class="block mt-0.5 w-full text-sm" type="text" placeholder="Request" maxlength="30" />
                                                <x-input-error :messages="$errors->get('emptyLabel')" class="mt-1" />
                                            </div>
                                            <div>
                                                <x-input-label value="Font" class="text-[10px]" />
                                                <div class="grid grid-cols-2 gap-1.5 mt-0.5">
                                                    @foreach (\App\Enums\SeatFont::cases() as $option)
                                                        <button type="button" wire:click="$set('font', '{{ $option->value }}')"
                                                            style="font-family: {{ $option->cssFontFamily() }};"
                                                            class="px-2 py-1 text-xs rounded-md border transition {{ $font === $option->value ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300' : 'border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                                                            {{ $option->label() }}
                                                        </button>
                                                    @endforeach
                                                </div>
                                                <x-input-error :messages="$errors->get('font')" class="mt-1" />
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Icon kotak kosong - PINDAH dari modal Edit Kursi ke sini, DI
                                         DALAM blok "enabled" (bagian dari skema lokal). --}}
                                    @if ($key === 'empty_icon')
                                        <div class="pt-1" x-data="{ preview: null }">
                                            <x-input-label for="emptyIconFile" value="Icon" class="text-[10px]" />
                                            <div class="flex items-center gap-2 mt-0.5">
                                                @if ($this->emptyIconUrl())
                                                    <img src="{{ $this->emptyIconUrl() }}" class="w-7 h-7 object-contain rounded border border-gray-200 dark:border-gray-600 flex-shrink-0">
                                                @endif
                                                <template x-if="preview">
                                                    <img :src="preview" class="w-7 h-7 object-contain rounded border border-gray-200 dark:border-gray-600 flex-shrink-0">
                                                </template>
                                                <input type="file" wire:model="emptyIconFile" id="emptyIconFile" accept="image/*"
                                                    x-on:change="
                                                        const file = $event.target.files[0];
                                                        if (! file) { preview = null; return; }
                                                        const reader = new FileReader();
                                                        reader.onload = (e) => preview = e.target.result;
                                                        reader.readAsDataURL(file);
                                                    "
                                                    class="block w-full text-xs text-gray-600 dark:text-gray-300 file:mr-2 file:py-1 file:px-2 file:rounded-md file:border-0 file:text-[10px] file:font-semibold file:bg-indigo-50 dark:file:bg-indigo-900/40 file:text-indigo-700 dark:file:text-indigo-300">
                                                @if ($this->emptyIconUrl())
                                                    <button type="button" wire:click="removeEmptyIcon"
                                                        class="flex-shrink-0 text-[10px] font-semibold text-gray-400 hover:text-red-500">
                                                        Hapus
                                                    </button>
                                                @endif
                                            </div>
                                            <p class="text-[10px] text-gray-400 mt-1">Kosongkan (jangan pilih file) utk fallback default (+).</p>
                                            <div wire:loading wire:target="emptyIconFile" class="text-[10px] text-gray-400 mt-1">Mengunggah...</div>
                                            <x-input-error :messages="$errors->get('emptyIconFile')" class="mt-1" />
                                        </div>
                                    @endif
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <div class="border-t border-gray-100 dark:border-gray-700 pt-3">
                        {{-- borderColor/emptyBgColor SUDAH toggle secara implisit (string kosong
                             = ikut GLOBAL project_lives.seat_border_color/seat_empty_bg_color,
                             diisi = override LOKAL) - toggleBorderColor()/toggleEmptyBgColor()
                             cuma nyediain tombol yang tampil SAMA kayak elemen lain di atas. --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="border border-gray-200 dark:border-gray-700 rounded-md p-3 space-y-2">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">Warna BG Kotak Kosong</span>
                                <button type="button" wire:click="toggleEmptyBgColor"
                                    class="flex-shrink-0 inline-flex items-center gap-1.5 rounded-full pl-1 pr-2 py-0.5 transition {{ $emptyBgColor !== '' ? 'bg-green-600' : 'bg-gray-300 dark:bg-gray-600' }}">
                                    <span class="relative inline-flex h-4 w-7 items-center rounded-full bg-black/20">
                                        <span class="inline-block h-3 w-3 transform rounded-full bg-white transition {{ $emptyBgColor !== '' ? 'translate-x-3.5' : 'translate-x-0.5' }}"></span>
                                    </span>
                                    <span class="text-[10px] font-semibold text-white">{{ $emptyBgColor !== '' ? 'Lokal' : 'Global' }}</span>
                                </button>
                            </div>

                            @if ($emptyBgColor !== '')
                                <p class="text-[10px] text-gray-400">Dipakai selama kotak ini belum ada interaksi & tidak ada hotkey warna aktif.</p>
                                <div class="flex items-center gap-2">
                                    <input type="color" wire:model="emptyBgColor" id="emptyBgColor"
                                        class="h-9 w-14 rounded-md border border-gray-200 dark:border-gray-600 cursor-pointer">
                                    <x-text-input wire:model="emptyBgColor" class="block w-full text-sm" type="text" />
                                </div>
                                <x-input-error :messages="$errors->get('emptyBgColor')" class="mt-1" />
                            @endif
                        </div>

                        <div class="border border-gray-200 dark:border-gray-700 rounded-md p-3 space-y-2">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">Border Kotak</span>
                                {{-- Satu toggle utk warna & tebal border sekaligus (bukan 2 toggle
                                     terpisah) - keduanya bagian dari 1 override "Border Kotak Ini". --}}
                                <button type="button" wire:click="toggleBorderColor"
                                    class="flex-shrink-0 inline-flex items-center gap-1.5 rounded-full pl-1 pr-2 py-0.5 transition {{ $borderColor !== '' ? 'bg-green-600' : 'bg-gray-300 dark:bg-gray-600' }}">
                                    <span class="relative inline-flex h-4 w-7 items-center rounded-full bg-black/20">
                                        <span class="inline-block h-3 w-3 transform rounded-full bg-white transition {{ $borderColor !== '' ? 'translate-x-3.5' : 'translate-x-0.5' }}"></span>
                                    </span>
                                    <span class="text-[10px] font-semibold text-white">{{ $borderColor !== '' ? 'Lokal' : 'Global' }}</span>
                                </button>
                            </div>

                            @if ($borderColor !== '')
                                <div>
                                    <x-input-label value="Warna" class="text-[10px]" />
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <input type="color" wire:model="borderColor" id="styleBorderColor"
                                            class="h-9 w-14 rounded-md border border-gray-200 dark:border-gray-600 cursor-pointer">
                                        <x-text-input wire:model="borderColor" class="block w-full text-sm" type="text" />
                                    </div>
                                    <x-input-error :messages="$errors->get('borderColor')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label value="Tebal (px)" class="text-[10px]" />
                                    <x-text-input wire:model="borderWidth" class="block w-full text-sm mt-0.5" type="number" min="0" max="20" />
                                    <x-input-error :messages="$errors->get('borderWidth')" class="mt-1" />
                                </div>
                            @endif
                        </div>
                        </div>
                    </div>

                    {{-- Batal & Simpan SENGAJA pakai class yang sama persis (bukan
                         <x-primary-button>, itu text-xs uppercase - beda ukuran/gaya dari
                         tombol Batal di sebelahnya) biar dua-duanya kelihatan sama besar. --}}
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="closeStyleEdit"
                            class="px-4 py-2 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-md hover:bg-gray-50 dark:hover:bg-gray-800">
                            Batal
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
