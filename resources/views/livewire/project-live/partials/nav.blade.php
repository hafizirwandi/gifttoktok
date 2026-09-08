{{--
    Menu header SATU-SATUNYA buat semua halaman "project-live/{id}/..." (Admin,
    Pemetaan Gift, Frame Host, Hotkey Warna, Event Trigger, Background) - dulu
    tiap halaman nulis markup <x-slot name="header"> sendiri-sendiri (detail-admin.
    blade.php punya menu LENGKAP, 5 halaman lain cuma link "Kembali ke Admin" doang,
    jadi TIDAK KONSISTEN) - sekarang cukup @include partial ini dgn $projectLive &
    $title, tab yang lagi aktif otomatis nge-highlight lewat request()->routeIs().
    Butuh $projectLive (App\Models\ProjectLive); $title opsional (default 'Admin').
--}}
@php
    $navLinks = [
        'project-live.admin' => 'Admin',
        'project-live.gift-mapping' => 'Pemetaan Gift',
        'project-live.frame-host' => 'Frame Host',
        'project-live.hotkey-color' => 'Hotkey Warna',
        'project-live.event-trigger' => 'Event Trigger',
        'project-live.background' => 'Background',
    ];
@endphp
<div class="flex items-center justify-between gap-3 flex-wrap">
    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight flex-shrink-0">
        {{ $title ?? 'Admin' }} — {{ $projectLive->name }}
    </h2>

    <nav class="flex items-center gap-1 flex-wrap">
        @can('manage', \App\Models\ProjectLive::class)
            <a href="{{ route('project-live.index') }}" wire:navigate
                class="text-xs text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 mr-2">&larr; Daftar Project</a>
        @endcan

        @foreach ($navLinks as $routeName => $label)
            <a href="{{ route($routeName, $projectLive) }}" wire:navigate
                class="px-2.5 py-1 rounded-md text-xs font-semibold transition {{ request()->routeIs($routeName) ? 'bg-indigo-600 text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                {{ $label }}
            </a>
        @endforeach

        <a href="{{ route('project-live.preview-live', $projectLive) }}" target="_blank" rel="noopener"
            class="px-2.5 py-1 rounded-md text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:bg-gray-100 dark:hover:bg-gray-700">
            Preview Live
        </a>
        <a href="{{ route('project-live.live', $projectLive) }}" target="_blank" rel="noopener"
            class="px-2.5 py-1 rounded-md text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:bg-gray-100 dark:hover:bg-gray-700">
            Buka Live &rarr;
        </a>
    </nav>
</div>
