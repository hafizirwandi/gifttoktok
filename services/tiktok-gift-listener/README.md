# TikTok Gift Listener

Service kecil yang jalan lokal di PC/laptop streamer (bareng TikTok LIVE Studio). Tugasnya cuma satu: dengar event gift dari 1 room TikTok LIVE, lalu kirim ke Laravel (GiftTokTok) supaya kotak di halaman Live terisi otomatis.

## Cara pakai (Windows)

1. Buka halaman **Admin** project di GiftTokTok, aktifkan **Auto Gift Mode**, isi **Username TikTok LIVE**, lalu salin 4 nilai dari panel info di bawahnya.
2. Copy `.env.example` jadi `.env` (di folder ini), isi 4 nilai tadi.
3. Double-click **`start.bat`** — otomatis `npm install` kalau belum pernah, lalu jalankan service di background (jendela tidak perlu dibiarkan terbuka).
4. Mulai live di TikTok LIVE Studio (boleh sebelum atau sesudah `start.bat`, service otomatis coba lagi tiap 10 detik sampai room-nya aktif).
5. Cek halaman Admin GiftTokTok — kalau sudah terhubung, muncul indikator hijau **"Terhubung ke TikTok LIVE"**.
6. Selesai live: double-click **`stop.bat`** untuk menghentikan service.

Log aktivitas ada di `listener.log` (dan error di `listener.err.log`) — buka pakai Notepad kalau perlu cek kenapa gift tidak masuk.

## Cara pakai (Mac/Linux, manual)

```
npm install
npm start
```
`Ctrl+C` untuk berhenti.

## Catatan

- Kalau Laravel/hosting-nya sedang tidak bisa diakses saat gift masuk, event itu akan di-log dan dilewati (dicoba ulang 3x dulu) — tidak bikin service ini crash.
- Indikator "Terhubung" di halaman Admin berdasarkan heartbeat yang dikirim tiap 20 detik selama service ini nyambung ke TikTok LIVE — kalau service dimatikan atau koneksi putus, indikatornya otomatis balik ke "Belum terhubung" dalam ~45 detik.
- Field pada event gift TikTok bisa sedikit berbeda tergantung versi `tiktok-live-connector` yang ter-install. Kalau data yang masuk ke GiftTokTok tidak sesuai (nama/avatar kosong terus), tambahkan `console.log(JSON.stringify(data, null, 2))` di `src/index.js` pada handler gift untuk lihat struktur data asli, lalu sesuaikan `extractGiftPayload()`.
