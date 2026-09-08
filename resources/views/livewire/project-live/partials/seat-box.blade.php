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
    // Tebal border - LOKAL per-kursi (project_live_details.border_width, satu toggle
    // dgn border_color di panel "Custom") kalau diisi, atau GLOBAL (project_lives.
    // seat_border_width, Admin) kalau tidak.
    $borderWidth = $detail['border_width'] ?? null;
    $borderWidth = $borderWidth !== null ? $borderWidth : $projectLive->seat_border_width;

    // Padding/tebal border/rounded border diatur admin (px literal, bukan skala persen
    // spt SIZE_FIELDS) - lihat App\Livewire\ProjectLive\DetailAdmin::BOX_STYLE_FIELDS.
    // Dulu hardcode "border-4 rounded-xl" via class Tailwind - sekarang inline style
    // krn nilainya dinamis per-project (sama alasannya dgn class Tailwind dinamis di
    // App\Enums\DisplayMode: class yg cuma ada di PHP tidak pernah ke-generate).
    $boxStyle = 'padding: '.$projectLive->seat_padding.'px; border-width: '.$borderWidth.'px; border-radius: '.$projectLive->seat_border_radius.'px;';

    // BG custom (App\Livewire\ProjectLive\Background, placement=seat) SENGAJA TANPA
    // padding - beda dari $boxStyle di atas yg padding-nya buat kasih jarak avatar dari
    // tepi kotak. BG harus penuh EDGE-TO-EDGE (itu maksudnya jadi "background"), padding
    // 'seat_padding' yg sama malah bikin video/gambar cuma ngisi tengah doang dgn celah
    // kosong di 4 sisi - border/radius tetap dipakai biar konsisten visual sama kotak lain.
    $bgBoxStyle = 'border-width: '.$borderWidth.'px; border-radius: '.$projectLive->seat_border_radius.'px;';

    // Efek pulse kursi: kotak yg POSISI-nya ada di seat_pulse_positions (checklist,
    // bisa lebih dari 1 kotak sekaligus) yang dapat animasi (keyframe-nya
    // didefinisikan sekali di live-show.blade.php, bukan di sini) - kecepatannya
    // SENGAJA dari frame_pulse_speed_ms (settingan Frame Host, bukan kolom sendiri)
    // - lihat App\Livewire\ProjectLive\FrameHost::updatedSeatPulsePositions().
    if ($projectLive->seat_pulse_enabled && in_array((int) $detail['position'], $projectLive->seat_pulse_positions ?? [], true)) {
        $boxStyle .= ' animation: gtt-seat-pulse '.$projectLive->frame_pulse_speed_ms.'ms ease-in-out infinite;';
        $bgBoxStyle .= ' animation: gtt-seat-pulse '.$projectLive->frame_pulse_speed_ms.'ms ease-in-out infinite;';
    }

    // Warna border - LOKAL per-kursi (tombol "Custom" di Preview Live, App\Models\
    // ProjectLiveDetail::border_color) kalau diisi, atau GLOBAL (project_lives.
    // seat_border_color, diatur di Admin) kalau tidak - keduanya kosong = pakai
    // default bawaan (class border-white/15 di bawah tetap kepakai apa adanya,
    // TIDAK ditimpa).
    $borderColor = ($detail['border_color'] ?? null) ?: $projectLive->seat_border_color;
    if (! empty($borderColor)) {
        $boxStyle .= ' border-color: '.$borderColor.';';
        $bgBoxStyle .= ' border-color: '.$borderColor.';';
    }

    // Suara video BG (App\Models\ProjectLiveBackground::audio_enabled) CUMA boleh
    // kedengaran kalau (1) admin nyalakan DI BG-nya sendiri, DAN (2) partial ini lagi
    // dirender di halaman Live ASLI, bukan Preview - $allowAudio dikirim eksplisit tiap
    // include (true dari live-show.blade.php, false dari preview-live.blade.php) SUPAYA
    // Preview Live tidak pernah keluar suara apa pun nilai audio_enabled-nya. Default
    // false kalau ada pemanggil yg lupa kirim param ini (fail-safe ke arah senyap).
    $videoMuted = ! (($allowAudio ?? false) && ($detail['background']['audio_enabled'] ?? false));

    // BUG YANG SUDAH KEJADIAN: attribute HTML "muted" CUMA dibaca browser SEKALI pas
    // elemen <video> pertama kali di-parse (nentuin defaultMuted) - begitu video-nya
    // sudah lanjut jalan, wire:poll berikutnya yang cuma nge-PATCH attribute "muted"
    // (elemen <video>-nya sendiri TETAP dipertahankan krn wire:key kotaknya tidak
    // berubah) TIDAK PERNAH benar2 mengubah status mute yang sedang diputar - jadi
    // toggle "Suara Video" di admin kelihatan tersimpan tapi suaranya tetap tidak
    // kedengaran. Solusinya video-nya sendiri dikasih wire:key TERPISAH yang ikut
    // berubah nilai kalau $videoMuted berubah, biar Livewire (morphdom) mengenali ini
    // sbg elemen BEDA dan bikin ulang <video>-nya dari nol (bukan cuma patch atribut) -
    // video-nya restart sebentar, tapi status mute-nya jadi benar2 ke-apply.
    $videoKey = 'bgvideo-'.$detail['id'].'-'.($videoMuted ? 'muted' : 'unmuted');

    // Size/offset EFEKTIF per elemen - LOKAL (tombol "Custom" per kotak di Preview
    // Live, project_live_details.style_overrides) kalau di-aktifkan admin, atau
    // GLOBAL (project_lives, sama spt sebelum fitur ini ada) kalau tidak. Satu
    // resolver dipakai bareng oleh SEMUA kotak (kursi normal & Co-Host) & SEMUA
    // cabang di bawah - lihat App\Support\SeatStyleResolver &
    // App\Livewire\ProjectLive\PreviewLive::STYLE_ELEMENTS.
    $coinStyle = \App\Support\SeatStyleResolver::resolve($detail, $projectLive, 'coin', 'coin_size', 'coin_offset_x', 'coin_offset_y');
    $nameStyle = \App\Support\SeatStyleResolver::resolve($detail, $projectLive, 'name', 'name_size', 'name_offset_x', 'name_offset_y');
    $giftBadgeStyle = \App\Support\SeatStyleResolver::resolve($detail, $projectLive, 'gift_badge', 'gift_badge_size', 'gift_badge_offset_x', 'gift_badge_offset_y');
    $micStyle = \App\Support\SeatStyleResolver::resolve($detail, $projectLive, 'mic', 'mic_size', 'mic_offset_x', 'mic_offset_y');
    $micIconUrl = $detail['local_mic_icon_url'] ?? $projectLive->micIconUrl();
    $emptyIconStyle = \App\Support\SeatStyleResolver::resolve($detail, $projectLive, 'empty_icon', 'empty_icon_size', 'empty_icon_offset_x', 'empty_icon_offset_y');
    $emptyLabelStyle = \App\Support\SeatStyleResolver::resolve($detail, $projectLive, 'empty_label', 'empty_label_size', 'empty_label_offset_x', 'empty_label_offset_y');
    // 'avatar' cuma pakai size & offset_y (panel Custom tidak nampilin offset_x-nya,
    // lihat PreviewLive::STYLE_ELEMENTS 'no_offset_x') - offset_x TETAP di-resolve
    // (nilainya 0 kalau lokal aktif) biar signature resolve() tetap generik.
    $avatarStyle = \App\Support\SeatStyleResolver::resolve($detail, $projectLive, 'avatar', 'avatar_size', 'avatar_offset_x', 'avatar_offset_y');

    // Tampil/Sembunyi EFEKTIF per elemen - LOKAL (toggle di dalam blok Lokal panel
    // "Custom") atau GLOBAL (project_lives, Admin) kalau tidak di-override - lihat
    // App\Support\SeatStyleResolver::isVisible().
    $coinVisible = \App\Support\SeatStyleResolver::isVisible($detail, $projectLive, 'coin', 'coin_visible');
    $nameVisible = \App\Support\SeatStyleResolver::isVisible($detail, $projectLive, 'name', 'name_visible');
    $giftBadgeVisible = \App\Support\SeatStyleResolver::isVisible($detail, $projectLive, 'gift_badge', 'gift_badge_visible');
    $emptyIconVisible = \App\Support\SeatStyleResolver::isVisible($detail, $projectLive, 'empty_icon', 'empty_icon_visible');
    $emptyLabelVisible = \App\Support\SeatStyleResolver::isVisible($detail, $projectLive, 'empty_label', 'empty_label_visible');
    // Mic BUKAN lewat isVisible() spt di atas - mekanisme lokalnya sendiri sudah ada
    // dari lama ($detail['mic_visible'], toggleModalMic()), jadi resolusinya khusus:
    // LOKAL cuma dipakai kalau style_overrides.mic.enabled aktif (toggle Tampil/
    // Sembunyi-nya sekarang ada DI DALAM blok Lokal itu), selain itu ikut GLOBAL.
    $micStyleEnabled = is_array($detail['style_overrides']['mic'] ?? null) && ($detail['style_overrides']['mic']['enabled'] ?? false);
    $micVisible = $micStyleEnabled ? ($detail['mic_visible'] ?? true) : $projectLive->mic_visible;
    // Host badge/nama - LOKAL per-BG (project_live_backgrounds.host_badge_visible/
    // host_name_visible, null = belum di-override) atau GLOBAL (project_lives) - cuma
    // relevan kalau $detail['background'] ada (role=host), aman dihitung di sini
    // regardless krn null-safe.
    $hostBadgeVisible = ($detail['background']['host_badge_visible'] ?? null) ?? $projectLive->host_badge_visible;
    $hostNameVisible = ($detail['background']['host_name_visible'] ?? null) ?? $projectLive->host_name_visible;
    // Teks/font/posisi tulisan badge "Host" - LOKAL per-BG (project_live_backgrounds.
    // host_badge_text/font/offset_x/offset_y, satu bundel - null semua = belum
    // di-override) atau GLOBAL (project_lives) kalau tidak - fallback akhir teks ke
    // literal "Host" kalau dua-duanya kosong. Lihat App\Livewire\ProjectLive\
    // PreviewLive::toggleHostBadgeCustom().
    $hostBadgeText = ($detail['background']['host_badge_text'] ?? null) ?: ($projectLive->host_badge_text ?: 'Host');
    $hostBadgeFont = ($detail['background']['host_badge_font'] ?? null) ?: $projectLive->host_badge_font;
    $hostBadgeOffsetX = ($detail['background']['host_badge_offset_x'] ?? null) ?? $projectLive->host_badge_offset_x;
    $hostBadgeOffsetY = ($detail['background']['host_badge_offset_y'] ?? null) ?? $projectLive->host_badge_offset_y;
@endphp
@if (($detail['background']['role'] ?? 'none') === 'co_host')
    {{-- Co-Host (App\Enums\SeatRole) - kotak yang jadi BG, media-nya (video/gambar) tampil
         PENUH edge-to-edge sama persis kayak role "Host" (BUKAN dikecilkan jadi avatar
         lingkaran spt versi sebelumnya) - tapi TETAP dikasih overlay coin/nama/mic spt
         kursi normal di atasnya. Kursi ini TETAP dikecualikan dari auto-gift selama
         background_id terisi (lihat GiftLeaderboardService::recalculate()) - nama/
         coin/mic diisi manual admin lewat Preview Live (App\Livewire\ProjectLive\
         PreviewLive::saveBgEdit()), bukan otomatis dari gift TikTok. --}}
    @php
        $coHostIsVideo = $detail['background']['type'] === 'video';
        $coinDisplay = \App\Support\CoinFormatter::format($detail['gift_total_value'] ?? 0);
        $seatFit = \App\Enums\BackgroundFit::from($detail['background']['fit_mode'])->cssObjectFit();
    @endphp
    <div wire:key="seat-{{ $detail['id'] }}"
        style="{{ $seatAreaStyle }} {{ $bgBoxStyle }} {{ $detail['background']['fit_mode'] === 'circle' ? 'background-color: '.$detail['background']['circle_bg_color'].';' : '' }}"
        class="relative w-full h-full overflow-hidden border-white/15">
        {{-- Media BG penuh (sama persis dgn cabang "elseif ($detail['background'])" di
             bawah utk role Host/polos) - fit_mode/offset/scale diatur admin lewat
             App\Livewire\ProjectLive\Background, BUKAN lagi avatar_size (setting itu
             cuma relevan buat avatar lingkaran kursi normal). Fit "circle" (App\Enums\
             BackgroundFit) beda total dari cover/contain/stretch: medianya TIDAK
             ngisi kotak edge-to-edge, cuma tampil sbg lingkaran di TENGAH kotak - luar
             lingkaran diisi warna circle_bg_color (diatur admin per-BG lewat
             App\Livewire\ProjectLive\Background, default abu2 #374151 - SAMA persis
             dgn avatar placeholder kursi kosong di bawah), bukan transparan/hitam
             polos. Diameter default
             62% dari lebar kotak (base yang SAMA dgn avatar_size normal `w-[62%]` di
             bawah, biar tidak kegedean edge-to-edge kalau admin belum sempat
             nge-tweak) - 'scale' 100% (default) = 62%, scale-nya jadi FAKTOR PENGALI
             (bukan literal %), offset_x/y menggeser posisi lingkarannya. --}}
        @if ($detail['background']['fit_mode'] === 'circle')
            <div class="absolute inset-0 flex items-center justify-center">
                <div class="aspect-square rounded-full overflow-hidden" style="width: {{ $detail['background']['scale'] * 0.62 }}%; transform: translate({{ $detail['background']['offset_x'] }}px, {{ $detail['background']['offset_y'] }}px);">
                    @if ($coHostIsVideo)
                        <video wire:key="{{ $videoKey }}-circle" src="{{ $detail['background']['url'] }}" autoplay loop playsinline {{ $videoMuted ? 'muted' : '' }}
                            class="w-full h-full object-cover"></video>
                    @else
                        <img src="{{ $detail['background']['url'] }}" alt="{{ $detail['name'] ?? '' }}" class="w-full h-full object-cover">
                    @endif
                </div>
            </div>
        @elseif ($coHostIsVideo)
            <video wire:key="{{ $videoKey }}-full" src="{{ $detail['background']['url'] }}" autoplay loop playsinline {{ $videoMuted ? 'muted' : '' }}
                style="width: 100%; height: 100%; object-fit: {{ $seatFit }}; transform: translate({{ $detail['background']['offset_x'] }}px, {{ $detail['background']['offset_y'] }}px) scale({{ $detail['background']['scale'] / 100 }});"></video>
        @else
            <img src="{{ $detail['background']['url'] }}" alt="{{ $detail['name'] ?? '' }}"
                style="width: 100%; height: 100%; object-fit: {{ $seatFit }}; transform: translate({{ $detail['background']['offset_x'] }}px, {{ $detail['background']['offset_y'] }}px) scale({{ $detail['background']['scale'] / 100 }});">
        @endif

        @if ($coinVisible)
            <!-- Badge coin -->
            <div class="absolute top-2 left-2 flex items-center gap-1.5 bg-black/60 rounded-full pl-1.5 pr-2.5 py-1" style="transform: translate({{ $coinStyle['offset_x'] }}px, {{ $coinStyle['offset_y'] }}px) scale({{ $coinStyle['size'] / 100 }}); transform-origin: top left;">
                <span class="w-4 h-4 rounded-full bg-sky-400 border-2 border-white flex items-center justify-center flex-shrink-0">
                    <svg viewBox="0 0 20 20" fill="currentColor" class="w-2.5 h-2.5 text-white">
                        <path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401Z" clip-rule="evenodd" />
                    </svg>
                </span>
                <span class="text-xs font-semibold text-white leading-none inline-block">{{ $coinDisplay }}</span>
            </div>
        @endif

        @if ($nameVisible)
            <!-- Badge nama -->
            <div class="absolute bottom-2 left-2 h-7 flex items-center gap-1.5 bg-black/60 rounded-full py-1 pl-2.5 pr-1" style="transform: translate({{ $nameStyle['offset_x'] }}px, {{ $nameStyle['offset_y'] }}px) scale({{ $nameStyle['size'] / 100 }}); transform-origin: bottom left;">
                <span class="text-xs font-medium text-white truncate max-w-[8ch]">{{ $detail['name'] ?? '' }}</span>
                <span class="w-4 h-4 rounded-full bg-white/20 flex items-center justify-center text-white text-xs leading-none flex-shrink-0">+</span>
            </div>
        @endif

        @if ($micVisible)
            <div class="absolute bottom-2 right-2 h-10 flex items-center justify-center" style="transform: translate({{ $micStyle['offset_x'] }}px, {{ $micStyle['offset_y'] }}px) scale({{ $micStyle['size'] / 100 }}); transform-origin: bottom right;">
                @if ($micIconUrl)
                    <img src="{{ $micIconUrl }}" alt="" class="w-5 h-5 object-contain drop-shadow">
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="w-5 h-5 text-white/80 drop-shadow">
                        <path d="M12 15a3 3 0 003-3V6a3 3 0 10-6 0v6a3 3 0 003 3z" stroke="currentColor" stroke-width="1.5"/>
                        <path d="M5 10v2a7 7 0 0014 0v-2M12 19v3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        <path d="M4 4l16 16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                @endif
            </div>
        @endif
    </div>
@elseif ($detail['background'] ?? null)
    {{-- Kotak ini dijadikan BG custom (App\Livewire\ProjectLive\Background, placement=seat)
         - menggantikan SELURUH isi kotak normal (avatar/coin/nama/mic/gift-badge), bukan
         cuma avatarnya. Kotak ini juga otomatis dikeluarkan dari auto-gift selama BG-nya
         aktif (lihat App\Services\GiftLeaderboardService::recalculate()). Role "Host"
         (App\Enums\SeatRole) nambah badge "Host" di pojok kiri atas, style-nya diatur
         admin lewat Preview Live (warna latar/icon/teks & ukuran, PreviewLive::saveBgEdit()). --}}
    @php $seatFit = \App\Enums\BackgroundFit::from($detail['background']['fit_mode'])->cssObjectFit(); @endphp
    <div wire:key="seat-{{ $detail['id'] }}"
        style="{{ $seatAreaStyle }} {{ $bgBoxStyle }} {{ $detail['background']['fit_mode'] === 'circle' ? 'background-color: '.$detail['background']['circle_bg_color'].';' : '' }}"
        class="relative w-full h-full overflow-hidden border-white/15">
        @if ($detail['background']['fit_mode'] === 'circle')
            {{-- Lingkaran di tengah kotak (App\Enums\BackgroundFit::Circle) - lihat
                 komentar lebih detail di cabang "co_host" di atas, markup-nya sama
                 persis (bg-gray-700 di luar lingkaran, diameter default 62% x scale). --}}
            <div class="absolute inset-0 flex items-center justify-center">
                <div class="aspect-square rounded-full overflow-hidden" style="width: {{ $detail['background']['scale'] * 0.62 }}%; transform: translate({{ $detail['background']['offset_x'] }}px, {{ $detail['background']['offset_y'] }}px);">
                    @if ($detail['background']['type'] === 'video')
                        <video wire:key="{{ $videoKey }}-circle" src="{{ $detail['background']['url'] }}" autoplay loop playsinline {{ $videoMuted ? 'muted' : '' }}
                            class="w-full h-full object-cover"></video>
                    @else
                        <img src="{{ $detail['background']['url'] }}" alt="" class="w-full h-full object-cover">
                    @endif
                </div>
            </div>
        @elseif ($detail['background']['type'] === 'video')
            <video wire:key="{{ $videoKey }}-full" src="{{ $detail['background']['url'] }}" autoplay loop playsinline {{ $videoMuted ? 'muted' : '' }}
                style="width: 100%; height: 100%; object-fit: {{ $seatFit }}; transform: translate({{ $detail['background']['offset_x'] }}px, {{ $detail['background']['offset_y'] }}px) scale({{ $detail['background']['scale'] / 100 }});"></video>
        @else
            <img src="{{ $detail['background']['url'] }}" alt=""
                style="width: 100%; height: 100%; object-fit: {{ $seatFit }}; transform: translate({{ $detail['background']['offset_x'] }}px, {{ $detail['background']['offset_y'] }}px) scale({{ $detail['background']['scale'] / 100 }});">
        @endif

        @if (($detail['background']['role'] ?? 'none') === 'host')
            @if ($hostBadgeVisible)
                <span class="absolute top-2 left-2 flex items-center gap-1 rounded-full px-2.5 py-1"
                    style="background: {{ $detail['background']['host_badge_bg_color'] }}; transform: translate({{ $hostBadgeOffsetX }}px, {{ $hostBadgeOffsetY }}px) scale({{ $detail['background']['host_badge_size'] / 100 }}); transform-origin: top left;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="{{ $detail['background']['host_badge_text_color'] }}" class="w-4 h-4">
                        <path d="M12 12a5 5 0 100-10 5 5 0 000 10zm0 2c-4.42 0-8 2.24-8 5v1h16v-1c0-2.76-3.58-5-8-5z"/>
                    </svg>
                    <span style="color: {{ $detail['background']['host_badge_text_color'] }}; {{ $hostBadgeFont ? 'font-family: '.\App\Enums\SeatFont::from($hostBadgeFont)->cssFontFamily().';' : '' }}" class="text-sm font-semibold">{{ $hostBadgeText }}</span>
                </span>
            @endif

            {{-- Nama Host: teks BOLD tanpa badge/pil putih (beda dari Badge Nama kursi
                 normal/Co-Host) di pojok kiri BAWAH, sesuai tampilan asli TikTok LIVE -
                 font/ukuran/posisi GLOBAL lewat App\Livewire\ProjectLive\DetailAdmin
                 (host_name_*), isi teksnya sendiri per kotak lewat PreviewLive::
                 saveBgEdit() ($detail['name'], field yang sama dgn Co-Host). --}}
            @if ($hostNameVisible && ($detail['name'] ?? null))
                <span class="absolute bottom-2 left-2 font-bold text-white"
                    style="text-shadow: 0 1px 3px rgba(0,0,0,.85); transform: translate({{ $projectLive->host_name_offset_x }}px, {{ $projectLive->host_name_offset_y }}px) scale({{ $projectLive->host_name_size / 100 }}); transform-origin: bottom left; {{ $projectLive->host_name_font ? 'font-family: '.\App\Enums\SeatFont::from($projectLive->host_name_font)->cssFontFamily().';' : '' }}">
                    {{ $detail['name'] }}
                </span>
            @endif
        @endif
    </div>
@elseif (($detail['status'] ?? 'hide') === 'show' && ($detail['name'] ?? null))
    @php
        $coinDisplay = \App\Support\CoinFormatter::format($detail['gift_total_value'] ?? 0);
    @endphp
    <div wire:key="seat-{{ $detail['id'] }}" wire:click="toggleClick({{ $detail['id'] }})"
        style="{{ $seatAreaStyle }} {{ $boxStyle }}"
        class="relative w-full h-full overflow-hidden border-white/15 cursor-pointer">
        <!-- Background: foto yang sama, diblur & digelapkan sedikit -->
        @if ($detail['img_url'] ?? null)
            <img src="{{ $detail['img_url'] }}" alt="" aria-hidden="true"
                class="absolute inset-0 w-full h-full object-cover scale-125 blur-md brightness-[0.45]">
        @else
            <div class="absolute inset-0" style="background: {{ $detail['dominant_color'] ?? '#111111' }};"></div>
        @endif

        <!-- Avatar: absolute (bukan h-full) supaya ukurannya tidak tergantung resolusi
             persentase tinggi elemen induk — itu yang sebelumnya bikin kotak jadi
             membesar/tidak stabil begitu foto avatar asli (bukan placeholder) dimuat. -->
        <div class="absolute inset-0 flex items-center justify-center">
            @if ($detail['img_url'] ?? null)
                <img src="{{ $detail['img_url'] }}" alt="{{ $detail['name'] ?? '' }}" class="w-[62%] aspect-square rounded-full object-cover ring-2 ring-white/20" style="transform: translate({{ $avatarStyle['offset_x'] }}px, {{ $avatarStyle['offset_y'] }}px) scale({{ $avatarStyle['size'] / 100 }});">
            @else
                <div class="w-[62%] aspect-square rounded-full bg-gray-700" style="transform: translate({{ $avatarStyle['offset_x'] }}px, {{ $avatarStyle['offset_y'] }}px) scale({{ $avatarStyle['size'] / 100 }});"></div>
            @endif
        </div>

        @if ($coinVisible)
            <!-- Badge coin: bintang biru + angka. Skala diterapkan ke SELURUH badge
                 (background+isi) via transform, bukan cuma teksnya, sesuai setting
                 "Ukuran Konten Kotak Live" di admin. -->
            <div class="absolute top-2 left-2 flex items-center gap-1.5 bg-black/60 rounded-full pl-1.5 pr-2.5 py-1" style="transform: translate({{ $coinStyle['offset_x'] }}px, {{ $coinStyle['offset_y'] }}px) scale({{ $coinStyle['size'] / 100 }}); transform-origin: top left;">
                <span class="w-4 h-4 rounded-full bg-sky-400 border-2 border-white flex items-center justify-center flex-shrink-0">
                    <svg viewBox="0 0 20 20" fill="currentColor" class="w-2.5 h-2.5 text-white">
                        <path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401Z" clip-rule="evenodd" />
                    </svg>
                </span>
                <span wire:key="coin-{{ $detail['id'] }}-{{ $detail['gift_total_value'] ?? 0 }}"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="text-xs font-semibold text-white leading-none inline-block">{{ $coinDisplay }}</span>
            </div>
        @endif

        @if ($nameVisible)
            <!-- Badge nama + plus -->
            <div class="absolute bottom-2 left-2 h-7 flex items-center gap-1.5 bg-black/60 rounded-full py-1 pl-2.5 pr-1" style="transform: translate({{ $nameStyle['offset_x'] }}px, {{ $nameStyle['offset_y'] }}px) scale({{ $nameStyle['size'] / 100 }}); transform-origin: bottom left;">
                <span class="text-xs font-medium text-white truncate max-w-[8ch]">{{ $detail['name'] ?? '' }}</span>
                <span class="w-4 h-4 rounded-full bg-white/20 flex items-center justify-center text-white text-xs leading-none flex-shrink-0">+</span>
            </div>
        @endif

        <!-- Mic mute: pojok kanan bawah, transparan tanpa badge - bisa disembunyikan
             PER KURSI lewat modal edit kursi di Preview Live (project_live_details.
             mic_visible), beda dari mic_size yg tetap satu setting global buat semua
             kotak. -->
        @if ($micVisible)
            <div class="absolute bottom-2 right-2 h-10 flex items-center justify-center" style="transform: translate({{ $micStyle['offset_x'] }}px, {{ $micStyle['offset_y'] }}px) scale({{ $micStyle['size'] / 100 }}); transform-origin: bottom right;">
                {{-- Icon mic custom (App\Models\ProjectLive::micIconUrl(), satu utk semua
                     kotak) - fallback ke SVG bawaan kalau belum ada yang di-upload. --}}
                @if ($micIconUrl)
                    <img src="{{ $micIconUrl }}" alt="" class="w-5 h-5 object-contain drop-shadow">
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="w-5 h-5 text-white/80 drop-shadow">
                        <path d="M12 15a3 3 0 003-3V6a3 3 0 10-6 0v6a3 3 0 003 3z" stroke="currentColor" stroke-width="1.5"/>
                        <path d="M5 10v2a7 7 0 0014 0v-2M12 19v3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        <path d="M4 4l16 16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                @endif
            </div>
        @endif

        <!-- Ikon gift terakhir: pojok kanan atas, muncul sebentar (fade-in lalu fade-out
             otomatis ~8 detik, lihat ProjectLiveDetail::toLiveArray()) — icon_url gift TUJUAN pemetaan
             (mis. Donat dipetakan ke gift "Lion") atau icon_url gift itu sendiri kalau
             belum dipetakan admin (lihat GiftMapping). -->
        @if ($giftBadgeVisible && ($detail['show_gift_badge'] ?? false) && ($detail['last_gift_icon_url'] ?? null))
            @php
                // Ukuran diatur lewat width/height (bukan transform:scale) supaya tidak
                // bentrok sama transform:scale yang sudah dipakai x-transition di bawah
                // buat animasi fade-in/out-nya — inline style="transform" bakal menimpa
                // total transform dari class Tailwind scale-50/scale-100/scale-75 itu.
                $giftBadgePx = 48 * $giftBadgeStyle['size'] / 100;
            @endphp
            <img wire:key="giftbadge-{{ $detail['id'] }}-{{ $detail['last_gift_at'] ?? 0 }}"
                src="{{ $detail['last_gift_icon_url'] }}" alt=""
                x-transition:enter="transition ease-out duration-500"
                x-transition:enter-start="opacity-0 scale-50"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-700"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-75"
                class="absolute object-contain drop-shadow-lg"
                style="width: {{ $giftBadgePx }}px; height: {{ $giftBadgePx }}px; top: calc(0.5rem + {{ $giftBadgeStyle['offset_y'] }}px); right: calc(0.5rem - {{ $giftBadgeStyle['offset_x'] }}px);">
        @endif
    </div>
@else
    @php
        // Prioritas warna kotak kosong (lihat App\Livewire\ProjectLive\HotkeyColor):
        // 1. Hotkey PER-KURSI yang lagi aktif buat kursi ini ($detail['active_hotkey_color'])
        // 2. Hotkey GLOBAL yang lagi aktif ($projectLive->active_hotkey_color) — menang
        //    buat SEMUA kotak kosong sekaligus
        // 3. Warna BG kotak kosong LOKAL kursi ini (project_live_details.empty_bg_color,
        //    tombol "Custom" per kotak di Preview Live) - tetap kalah dari hotkey (1 & 2)
        //    krn hotkey sifatnya live/dinamis pas siaran, ini cuma warna "istirahat"-nya.
        // 4. Warna BG kotak kosong GLOBAL (project_lives.seat_empty_bg_color, Admin)
        // 5. Hitam (default paling akhir, kalau semuanya di atas kosong)
        $activeColor = ($detail['active_hotkey_color'] ?? null) ?: $projectLive->active_hotkey_color;
        $emptyColor = $activeColor ?: ($detail['empty_bg_color'] ?? null) ?: $projectLive->seat_empty_bg_color ?: '#000000';
    @endphp
    <div wire:key="seat-{{ $detail['id'] }}" wire:click="toggleClick({{ $detail['id'] }})"
        class="relative w-full h-full border-white/10 flex flex-col items-center justify-center gap-2 cursor-pointer transition-colors duration-500"
        style="background: {{ $emptyColor }}; {{ $seatAreaStyle }} {{ $boxStyle }}">
        {{-- Status "hide" = admin belum approve kursi ini sama sekali (beda dari status
             "show" tanpa nama yg cuma nunggu trigger operator/gift) - icon DAN teks label
             kotak kosong ("Request" dst) SENGAJA tidak muncul kalau statusnya hide (cuma
             warna latarnya yg tetap tampil), biar kelihatan beda dari kursi yg memang
             sedang menunggu. --}}
        @if (($detail['status'] ?? 'hide') !== 'hide')
            {{-- Naik/turun icon & teks diatur TERPISAH (App\Livewire\ProjectLive\
                 DetailAdmin::BOX_STYLE_FIELDS empty_icon_offset_y/empty_label_offset_y),
                 masing-masing translateY sendiri, supaya independen satu sama lain. --}}
            @if ($emptyIconVisible)
                <div class="text-white/70 text-4xl leading-none flex items-center justify-center" style="transform: translate({{ $emptyIconStyle['offset_x'] }}px, {{ $emptyIconStyle['offset_y'] }}px) scale({{ $emptyIconStyle['size'] / 100 }});">
                    {{-- Icon kotak kosong LOKAL per-kursi (App\Models\ProjectLiveDetail::
                         emptyIconUrl(), diatur lewat tombol "Custom" di Preview Live), atau
                         icon GLOBAL (App\Models\ProjectLive::emptyIconUrl(), Admin) kalau
                         kursi ini belum punya sendiri - fallback akhir ke '+' kalau
                         dua-duanya belum diisi. --}}
                    @php $emptyIconUrl = ($detail['empty_icon_url'] ?? null) ?: $projectLive->emptyIconUrl(); @endphp
                    @if ($emptyIconUrl)
                        <img src="{{ $emptyIconUrl }}" alt="" class="w-9 h-9 object-contain">
                    @else
                        +
                    @endif
                </div>
            @endif
            @if ($emptyLabelVisible)
                {{-- Font teks kotak kosong LOKAL per-kursi (App\Enums\SeatFont, diatur
                     lewat tombol "Custom" di Preview Live), atau font GLOBAL
                     (project_lives.empty_label_font, Admin) kalau kursi ini belum punya
                     sendiri - null/kosong dua-duanya = default (Figtree bawaan). --}}
                @php $emptyLabelFont = ($detail['font'] ?? null) ?: $projectLive->empty_label_font; @endphp
                <span class="text-lg text-white/50 font-medium" style="transform: translate({{ $emptyLabelStyle['offset_x'] }}px, {{ $emptyLabelStyle['offset_y'] }}px) scale({{ $emptyLabelStyle['size'] / 100 }}); display: inline-block; {{ $emptyLabelFont ? 'font-family: '.\App\Enums\SeatFont::from($emptyLabelFont)->cssFontFamily().';' : '' }}">{{ $detail['empty_label'] ?? '' ?: 'Request' }}</span>
            @endif
        @endif
    </div>
@endif
