{{--
    Satu kotak kursi (dipakai berulang di semua mode tampilan) — butuh $detail (array)
    dan $mode (App\Enums\DisplayMode). grid-area cuma perlu ditulis kalau mode-nya
    "mosaik" (gridTemplateAreas() != null, lihat App\Enums\DisplayMode) - mode grid
    seragam otomatis urut ikut DOM tanpa perlu grid-area sama sekali. seatStyleOverrides()
    nambahin CSS ekstra per POSISI kursi tertentu (mis. aspect-ratio:1 biar persegi
    asli di Kisi Dinamis/Meja Bundar II) - lihat komentar method itu.
--}}
@php
    $seatAreaStyle = ($mode->gridTemplateAreas() ? 'grid-area: s'.$detail['position'].';' : '')
        .($mode->seatStyleOverrides()[$detail['position']] ?? '');
    // Padding/tebal border/rounded border diatur admin (px literal, bukan skala persen
    // spt SIZE_FIELDS) - lihat App\Livewire\ProjectLive\DetailAdmin::BOX_STYLE_FIELDS.
    // Dulu hardcode "border-4 rounded-xl" via class Tailwind - sekarang inline style
    // krn nilainya dinamis per-project (sama alasannya dgn class Tailwind dinamis di
    // App\Enums\DisplayMode: class yg cuma ada di PHP tidak pernah ke-generate).
    $boxStyle = 'padding: '.$projectLive->seat_padding.'px; border-width: '.$projectLive->seat_border_width.'px; border-radius: '.$projectLive->seat_border_radius.'px;';

    // Efek pulse kursi: cuma kotak yg POSISI-nya cocok yg dapat animasi (keyframe-nya
    // didefinisikan sekali di live-show.blade.php, bukan di sini) - lihat
    // App\Livewire\ProjectLive\DetailAdmin::saveSeatPulse().
    if ($projectLive->seat_pulse_enabled && (int) $detail['position'] === (int) $projectLive->seat_pulse_position) {
        $boxStyle .= ' animation: gtt-seat-pulse '.$projectLive->seat_pulse_speed_ms.'ms ease-in-out infinite;';
    }
@endphp
@if ($detail['status'] === 'show' && $detail['name'])
    @php
        $coinDisplay = \App\Support\CoinFormatter::format($detail['gift_total_value']);
    @endphp
    <div wire:key="seat-{{ $detail['id'] }}" wire:click="toggleClick({{ $detail['id'] }})"
        style="{{ $seatAreaStyle }} {{ $boxStyle }}"
        class="relative w-full h-full overflow-hidden border-white/15 cursor-pointer">
        <!-- Background: foto yang sama, diblur & digelapkan sedikit -->
        @if ($detail['img_url'])
            <img src="{{ $detail['img_url'] }}" alt="" aria-hidden="true"
                class="absolute inset-0 w-full h-full object-cover scale-125 blur-md brightness-[0.45]">
        @else
            <div class="absolute inset-0" style="background: {{ $detail['dominant_color'] }};"></div>
        @endif

        <!-- Avatar: absolute (bukan h-full) supaya ukurannya tidak tergantung resolusi
             persentase tinggi elemen induk — itu yang sebelumnya bikin kotak jadi
             membesar/tidak stabil begitu foto avatar asli (bukan placeholder) dimuat. -->
        <div class="absolute inset-0 flex items-center justify-center">
            @if ($detail['img_url'])
                <img src="{{ $detail['img_url'] }}" alt="{{ $detail['name'] }}" class="w-[62%] aspect-square rounded-full object-cover ring-2 ring-white/20" style="transform: scale({{ $projectLive->avatar_size / 100 }});">
            @else
                <div class="w-[62%] aspect-square rounded-full bg-gray-700" style="transform: scale({{ $projectLive->avatar_size / 100 }});"></div>
            @endif
        </div>

        <!-- Badge coin: bintang biru + angka. Skala diterapkan ke SELURUH badge
             (background+isi) via transform, bukan cuma teksnya, sesuai setting
             "Ukuran Konten Kotak Live" di admin. -->
        <div class="absolute top-2 left-2 flex items-center gap-1.5 bg-black/60 rounded-full pl-1.5 pr-2.5 py-1" style="transform: scale({{ $projectLive->coin_size / 100 }}); transform-origin: top left;">
            <span class="w-4 h-4 rounded-full bg-sky-400 border-2 border-white flex items-center justify-center flex-shrink-0">
                <svg viewBox="0 0 20 20" fill="currentColor" class="w-2.5 h-2.5 text-white">
                    <path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401Z" clip-rule="evenodd" />
                </svg>
            </span>
            <span wire:key="coin-{{ $detail['id'] }}-{{ $detail['gift_total_value'] }}"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="text-xs font-semibold text-white leading-none inline-block">{{ $coinDisplay }}</span>
        </div>

        <!-- Badge nama + plus -->
        <div class="absolute bottom-2 left-2 h-7 flex items-center gap-1.5 bg-black/60 rounded-full py-1 pl-2.5 pr-1" style="transform: scale({{ $projectLive->name_size / 100 }}); transform-origin: bottom left;">
            <span class="text-xs font-medium text-white truncate max-w-[8ch]">{{ $detail['name'] }}</span>
            <span class="w-4 h-4 rounded-full bg-white/20 flex items-center justify-center text-white text-xs leading-none flex-shrink-0">+</span>
        </div>

        <!-- Mic mute: pojok kanan bawah, transparan tanpa badge -->
        <div class="absolute bottom-2 right-2 h-10 flex items-center justify-center" style="transform: scale({{ $projectLive->mic_size / 100 }}); transform-origin: bottom right;">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="w-5 h-5 text-white/80 drop-shadow">
                <path d="M12 15a3 3 0 003-3V6a3 3 0 10-6 0v6a3 3 0 003 3z" stroke="currentColor" stroke-width="1.5"/>
                <path d="M5 10v2a7 7 0 0014 0v-2M12 19v3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                <path d="M4 4l16 16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
        </div>

        <!-- Ikon gift terakhir: pojok kanan atas, muncul sebentar (fade-in lalu fade-out
             otomatis ~8 detik, lihat LiveShow::toArray()) — icon_url gift TUJUAN pemetaan
             (mis. Donat dipetakan ke gift "Lion") atau icon_url gift itu sendiri kalau
             belum dipetakan admin (lihat GiftMapping). -->
        @if (($detail['show_gift_badge'] ?? false) && ($detail['last_gift_icon_url'] ?? null))
            @php
                // Ukuran diatur lewat width/height (bukan transform:scale) supaya tidak
                // bentrok sama transform:scale yang sudah dipakai x-transition di bawah
                // buat animasi fade-in/out-nya — inline style="transform" bakal menimpa
                // total transform dari class Tailwind scale-50/scale-100/scale-75 itu.
                $giftBadgePx = 48 * $projectLive->gift_badge_size / 100;
            @endphp
            <img wire:key="giftbadge-{{ $detail['id'] }}-{{ $detail['last_gift_at'] ?? 0 }}"
                src="{{ $detail['last_gift_icon_url'] }}" alt=""
                x-transition:enter="transition ease-out duration-500"
                x-transition:enter-start="opacity-0 scale-50"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-700"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-75"
                class="absolute top-2 right-2 object-contain drop-shadow-lg"
                style="width: {{ $giftBadgePx }}px; height: {{ $giftBadgePx }}px;">
        @endif
    </div>
@else
    @php
        // Prioritas warna kotak kosong (lihat App\Livewire\ProjectLive\HotkeyColor):
        // 1. Hotkey PER-KURSI yang lagi aktif buat kursi ini ($detail['active_hotkey_color'])
        // 2. Hotkey GLOBAL yang lagi aktif ($projectLive->active_hotkey_color) — menang
        //    buat SEMUA kotak kosong sekaligus
        // 3. Hitam (default, kalau tidak ada hotkey yang aktif)
        $activeColor = ($detail['active_hotkey_color'] ?? null) ?: $projectLive->active_hotkey_color;
        $emptyColor = $activeColor ?: '#000000';
    @endphp
    <div wire:key="seat-{{ $detail['id'] }}" wire:click="toggleClick({{ $detail['id'] }})"
        class="relative w-full h-full border-white/10 flex flex-col items-center justify-center gap-2 cursor-pointer transition-colors duration-500"
        style="background: {{ $emptyColor }}; {{ $seatAreaStyle }} {{ $boxStyle }}">
        {{-- Status "hide" = admin belum approve kursi ini sama sekali (beda dari status
             "show" tanpa nama yg cuma nunggu trigger operator/gift) - icon DAN teks label
             kotak kosong ("Request" dst) SENGAJA tidak muncul kalau statusnya hide (cuma
             warna latarnya yg tetap tampil), biar kelihatan beda dari kursi yg memang
             sedang menunggu. --}}
        @if ($detail['status'] !== 'hide')
            <div class="text-white/70 text-4xl leading-none" style="transform: scale({{ $projectLive->empty_icon_size / 100 }});">
                {{ $detail['empty_icon'] ?? '' ?: '+' }}
            </div>
            <span class="text-lg text-white/50 font-medium" style="transform: scale({{ $projectLive->empty_label_size / 100 }}); display: inline-block;">{{ $detail['empty_label'] ?? '' ?: 'Request' }}</span>
        @endif
    </div>
@endif
