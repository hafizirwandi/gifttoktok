# PRD — GiftTokTok

## 1. Latar Belakang

Saat live streaming TikTok dengan banyak tamu/co-host (multi-guest), operator live perlu cara cepat untuk menampilkan atau menyembunyikan "kursi" tamu di layar tanpa harus bolak-balik ke panel admin saat siaran berlangsung. GiftTokTok adalah tool internal untuk mengelola kursi tamu tersebut: superadmin menyiapkan data tiap kursi (foto, nama, jumlah follower, tombol cepat/hotkey) dari halaman admin, lalu operator (akun live) membuka halaman tampilan Live dan menekan tombol keyboard (hotkey) untuk menampilkan/menyembunyikan kursi tamu secara instan saat siaran berjalan.

Referensi tampilan: `assets/acuan.jpeg`.

## 2. Tujuan

- Superadmin dapat membuat & mengelola banyak "Project Live", masing-masing mewakili satu sesi/akun live yang dikelola.
- Tiap Project Live punya 8 kursi tamu yang bisa diatur satu per satu (foto, nama, follower, hotkey, status tampil/sembunyi).
- Operator (akun live) dapat membuka tampilan Live dan mengontrol kursi tamu secara real-time memakai keyboard, tanpa perlu klik-klik manual saat siaran berjalan.
- Perubahan yang dilakukan superadmin dari panel admin (misal menyembunyikan kursi) langsung tercermin di halaman Live yang sedang dibuka operator.

## 3. Target Pengguna

| Role | Deskripsi | Akses |
|---|---|---|
| **superadmin** | Mengelola seluruh Project Live, mengatur data 8 kursi tiap project, mengelola user & role | Semua halaman (list project, admin detail, manajemen user) |
| **live** (akun live) | Operator yang menjalankan siaran, hanya mengurus project yang menjadi tanggung jawabnya | Hanya halaman tampilan Live untuk project miliknya sendiri |

## 4. Fitur

### 4.1 User, Role & Permission
- Menggunakan Spatie laravel-permission.
- 2 role: `superadmin` dan `live`.
- Superadmin membuat akun untuk role `live` dan meng-assign-nya ke satu Project Live.

### 4.2 Project Live (fitur utama)
Field: `nama`, `status` (`live` / `off`), `nama akun` (nama akun TikTok), `desc`.

- Halaman list (khusus superadmin): menampilkan semua project dengan badge status, nama akun, deskripsi.
- Tiap baris punya 2 tombol aksi:
  - **Admin** → membuka halaman Project Live Detail untuk mengedit 8 kursi.
  - **Live** → membuka halaman tampilan Live (sesuai acuan.jpeg).
- Status `live`/`off` berfungsi sebagai gerbang akses: kalau `off`, halaman Live menampilkan pesan "Live belum dimulai" dan hotkey tidak aktif.

### 4.3 Project Live Detail (8 kursi)
Setiap Project Live otomatis memiliki 8 kursi (posisi 1–8) saat pertama dibuat. Tiap kursi punya field:

- `position` — urutan tampil di grid (2 kolom x 4 baris).
- `img` — foto tamu (upload).
- `name` — nama tamu.
- `follower` — jumlah follower (mis. "754,7K").
- `hotkey` — 1 karakter tombol keyboard (unik per project, mis. `1`–`8`).
- `status` — `hide` atau `show`.
- `dominant_color` — warna dominan hasil ekstraksi otomatis dari tepi foto saat upload, dipakai sebagai warna gradasi latar kursi.

Halaman Admin Detail menampilkan 8 kotak yang masing-masing bisa diedit langsung (upload foto, nama, follower, hotkey, toggle status).

### 4.4 Halaman Tampilan Live
Mengikuti tata letak `acuan.jpeg`, dark mode:

- Panel kiri: area konten utama (placeholder).
- Panel kanan: grid 2x4 (8 kotak) sesuai 8 kursi.
  - **Kursi status `show`**: badge jumlah follower di pojok kiri atas, foto bulat di tengah, latar kotak berupa gradasi warna sesuai `dominant_color`, badge nama + ikon "+" di bawah foto, ikon mic (nonaktif/mute) di pojok.
  - **Kursi status `hide`**: latar hitam polos, ikon "+" besar dan teks "Request" di tengah.

### 4.5 Mekanisme Hotkey
Selama halaman Live terbuka, operator dapat menekan tombol keyboard sesuai `hotkey` tiap kursi:

- Kursi `hide` → tekan hotkey → kursi berubah `show`, menampilkan data terbaru (foto/nama/follower) dari database.
- Kursi `show` → tekan hotkey yang sama lagi → data kursi di-refresh dari database (foto/nama/follower terbaru), status tetap `show`.
- Jika superadmin mengubah status kursi menjadi `hide` dari halaman Admin sementara halaman Live sedang terbuka, kursi tersebut otomatis ikut berubah menjadi `hide` di layar Live tanpa perlu operator menekan hotkey (sinkron berkala).
- Jika status Project Live diubah menjadi `off`, halaman Live berhenti menampilkan grid dan hotkey tidak aktif.

## 5. Alur Penggunaan

1. Superadmin login → membuat akun baru dengan role `live` untuk operator.
2. Superadmin membuat Project Live baru (nama, nama akun, desc) → sistem otomatis membuat 8 kursi kosong (status `hide`).
3. Superadmin meng-assign akun `live` ke Project Live tersebut.
4. Superadmin membuka tombol **Admin** pada project → mengisi/menyunting tiap kursi: upload foto (warna dominan diekstrak otomatis), nama, follower, hotkey.
5. Superadmin mengubah status Project Live menjadi `live` saat sesi siaran dimulai.
6. Operator (akun `live`) login → membuka tombol **Live** pada project miliknya → halaman tampilan Live terbuka.
7. Operator menekan tombol keyboard sesuai hotkey untuk menampilkan/menyembunyikan kursi tamu secara real-time selama siaran.
8. Superadmin bisa sewaktu-waktu mengubah data kursi dari halaman Admin; perubahan tercermin otomatis di halaman Live operator.
9. Setelah siaran selesai, superadmin mengubah status Project Live menjadi `off`.

## 6. Batasan & Asumsi

- Jumlah kursi tetap 8 per Project Live (tidak bisa ditambah/dikurangi).
- Hotkey adalah 1 karakter, harus unik dalam satu Project Live.
- Satu Project Live di-assign ke maksimal satu akun `live`.
- Sinkronisasi perubahan dari Admin ke halaman Live menggunakan polling berkala (bukan real-time WebSocket) pada versi awal; dapat ditingkatkan ke broadcasting (Laravel Reverb) di iterasi berikutnya jika dibutuhkan latensi lebih rendah.
- Tampilan dioptimalkan untuk mobile view namun tetap responsif di layar besar.

## 7. Tech Stack

- Laravel 12
- Livewire 3 + Alpine.js
- TailwindCSS
- Spatie laravel-permission
