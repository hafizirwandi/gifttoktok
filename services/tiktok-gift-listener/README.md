# TikTok Gift Listener

Service kecil yang jalan lokal di PC/laptop streamer (bareng TikTok LIVE Studio). Tugasnya cuma satu: dengar event gift dari 1 room TikTok LIVE, lalu kirim ke Laravel (GiftTokTok) supaya kotak di halaman Live terisi otomatis.

## Cara pakai (Windows)

1. Buka halaman **Admin** project di GiftTokTok, aktifkan **Auto Gift Mode**, isi **Username TikTok LIVE**, lalu salin 4 nilai dari panel info di bawahnya.
2. Copy `.env.example` jadi `.env` (di folder ini), isi 4 nilai tadi.
3. Double-click **`start.bat`** — otomatis `npm install` kalau belum pernah, lalu membuka **jendela baru** yang menampilkan log live service ini (persis seperti menjalankan `npm start` manual). **Biarkan jendela itu tetap terbuka** selama live berlangsung — menutupnya = mematikan service. `start.bat` juga otomatis menjalankan **queue worker Laravel** (`php artisan queue:work`) di background tersembunyi — ini yang memproses trigger Event Trigger tipe **like/chat** (lihat menu Event Trigger di Admin project); butuh `php` ada di PATH Windows (cek: `php -v`), kalau belum ada akan muncul pesan gagal tapi listener gift tetap jalan normal.
4. Mulai live di TikTok LIVE Studio (boleh sebelum atau sesudah `start.bat`, service otomatis coba lagi tiap 10 detik sampai room-nya aktif).
5. Cek halaman Admin GiftTokTok — kalau sudah terhubung, muncul indikator hijau **"Terhubung ke TikTok LIVE"**.
6. Selesai live: double-click **`stop.bat`** untuk menghentikan service dan queue worker-nya sekaligus (atau tutup langsung jendela log listener-nya — tapi queue worker tetap perlu dihentikan lewat `stop.bat` karena jalan tersembunyi tanpa jendela).

Untuk buka halaman web GiftTokTok-nya sendiri (Admin/Live), pakai **`start-web.bat`**/**`stop-web.bat`** yang terpisah di folder root project (`C:\xampp\htdocs\gifttoktok`, bukan folder ini) — jalankan `php artisan serve` di background lalu buka Chrome otomatis ke `http://127.0.0.1:8000`, tidak perlu setup Apache/XAMPP vhost sama sekali (vhost pernah terbukti gagal baca `.env` di sebagian komputer, `php artisan serve` tidak kena masalah itu). Dua hal ini independen dari listener di folder ini — bisa jalanin listener tanpa web server (kalau sudah buka webnya lewat cara lain), atau sebaliknya.

Kalau gift tidak masuk, cek langsung di jendela log itu — error TikTok/koneksi/webhook langsung kelihatan real-time di sana, tidak perlu buka file log terpisah lagi.

**`start.bat` "gagal" padahal service sudah mati?** Sekarang start.bat mengecek dulu apakah PID di `listener.pid` beneran masih proses node yang hidup (bukan cuma percaya file-nya ada) — kalau ternyata basi (proses sudah mati tapi file belum sempat dibersihkan, misal PC restart mendadak), akan dibersihkan otomatis lalu tetap jalan, bukan menolak dengan pesan "sudah jalan" yang keliru.

## Cara pakai (Mac/Linux, manual)

```
npm install
npm start
```
`Ctrl+C` untuk berhenti.

## Catatan

- **Sering muncul "signing server rate-limit" / `SignatureRateLimitError` / retry-after di log?** Tanpa API key, SEMUA pengguna `tiktok-live-connector` di seluruh dunia berbagi satu jatah rate-limit anonim yang sama di signing server TikTok (dioperasikan pihak ketiga, Euler Stream) — gampang penuh, apalagi kalau lagi jam ramai. Solusinya: daftar API key GRATIS di [eulerstream.com](https://www.eulerstream.com), lalu isi `TIKTOK_SIGN_API_KEY` di `.env` (lihat `.env.example`) — dapat jatah rate-limit sendiri, terpisah dari pengguna lain. Restart `start.bat` setelah mengisinya.
- **Habis `git pull` kode Laravel yang baru? Selalu `stop.bat` lalu `start.bat` lagi.** Queue worker (`php artisan queue:work`) memuat semua kode PHP cuma SEKALI saat pertama kali dijalankan dan terus memakainya selama proses itu hidup — kalau ada kode baru di-pull sementara queue worker masih jalan dari SEBELUM pull, dia tetap menjalankan kode LAMA sampai proses itu di-restart. Ini penyebab paling umum kalau suatu perbaikan "sudah di-push tapi kok masih kelakuan lama" — restart start.bat/stop.bat dulu sebelum lapor bug.
- Kalau Laravel/hosting-nya sedang tidak bisa diakses saat gift masuk, event itu akan di-log dan dilewati (dicoba ulang 3x dulu) — tidak bikin service ini crash.
- Indikator "Terhubung" di halaman Admin berdasarkan heartbeat yang dikirim tiap 20 detik selama service ini nyambung ke TikTok LIVE — kalau service dimatikan atau koneksi putus, indikatornya otomatis balik ke "Belum terhubung" dalam ~45 detik.
- Field pada event gift TikTok bisa sedikit berbeda tergantung versi `tiktok-live-connector` yang ter-install. Kalau data yang masuk ke GiftTokTok tidak sesuai (nama/avatar kosong terus), tambahkan `console.log(JSON.stringify(data, null, 2))` di `src/index.js` pada handler gift untuk lihat struktur data asli, lalu sesuaikan `extractGiftPayload()`.
