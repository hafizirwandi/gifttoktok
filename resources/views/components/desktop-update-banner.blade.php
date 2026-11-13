{{--
    Banner "versi baru tersedia" - mode desktop (installer Windows) saja, superadmin saja.
    Status di-fetch async (bukan saat render) supaya halaman tidak ikut lambat waktu
    GitHub API lambat/offline. Lihat App\Http\Controllers\Desktop\UpdateController.
--}}
<div x-data="{
        info: null,
        state: 'idle',
        message: '',
        async init() {
            try {
                const res = await fetch(@js(route('desktop.update.status')), { headers: { Accept: 'application/json' } });
                if (res.ok) this.info = await res.json();
            } catch (e) {}
        },
        async install() {
            if (!confirm('Update GiftTokTok ke versi ' + this.info.latest + ' sekarang? Aplikasi akan tertutup sebentar lalu terbuka lagi otomatis.')) return;
            this.state = 'downloading';
            try {
                const res = await fetch(@js(route('desktop.update.install')), {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                });
                const data = await res.json();
                this.message = data.message;
                this.state = res.ok ? 'installing' : 'error';
            } catch (e) {
                this.message = 'Gagal menghubungi server update.';
                this.state = 'error';
            }
        },
    }"
    x-show="info && info.available"
    x-cloak
    class="bg-indigo-600 text-white"
>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2 flex flex-wrap items-center justify-between gap-2 text-sm">
        <p>
            <template x-if="state === 'idle'">
                <span>Versi baru <span class="font-semibold" x-text="info?.latest"></span> tersedia (terpasang: <span x-text="info?.current"></span>).</span>
            </template>
            <template x-if="state === 'downloading'">
                <span>Mengunduh update… jangan tutup halaman ini.</span>
            </template>
            <template x-if="state === 'installing' || state === 'error'">
                <span x-text="message"></span>
            </template>
        </p>
        <div class="flex items-center gap-2">
            <a x-show="info?.url" :href="info?.url" target="_blank" class="underline text-indigo-100 hover:text-white">Lihat perubahan</a>
            <button type="button" x-show="state === 'idle' || state === 'error'" x-on:click="install()"
                class="inline-flex items-center px-3 py-1 bg-white text-indigo-700 font-semibold rounded hover:bg-indigo-50">
                Update sekarang
            </button>
        </div>
    </div>
</div>
