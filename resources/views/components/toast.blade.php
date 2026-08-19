{{--
    Popup notifikasi sukses global — dengar event browser "notify" (dipicu lewat
    $this->dispatch('notify', message: '...') di Livewire component manapun),
    bukan alert() bawaan JS. Ditaruh sekali di layouts/app.blade.php, jalan di
    semua halaman admin.
--}}
<div
    x-data="{ show: false, message: '' }"
    x-on:notify.window="message = $event.detail.message; show = true; clearTimeout(window.__toastTimer); window.__toastTimer = setTimeout(() => show = false, 3000)"
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-2"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 translate-y-2"
    class="fixed bottom-4 right-4 z-[100] flex items-center gap-2 bg-gray-900 dark:bg-gray-700 text-white text-sm font-medium pl-3 pr-4 py-3 rounded-lg shadow-xl"
    style="display: none;"
>
    <span class="w-5 h-5 rounded-full bg-green-500 flex items-center justify-center flex-shrink-0">
        <svg viewBox="0 0 20 20" fill="currentColor" class="w-3 h-3 text-white">
            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
        </svg>
    </span>
    <span x-text="message"></span>
</div>
