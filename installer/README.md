# Installer Windows GiftTokTok

GiftTokTok dibungkus jadi satu file `GiftTokTok-Setup-x.y.z.exe`. User cukup klik
Next → Next → Finish. PHP, Node, dan database (SQLite) sudah ikut di dalamnya,
jadi user tidak perlu install XAMPP, MySQL, Composer, maupun mengatur vhost.

## Rilis versi baru

```bash
git tag v1.2.0
git push origin v1.2.0
```

Setelah itu GitHub Actions ([release.yml](../.github/workflows/release.yml)) otomatis
melakukan build, menjalankan smoke test, dan meng-upload `.exe` ke **GitHub Releases**
(sekitar 10 menit). Aplikasi yang sudah terpasang di PC user akan menampilkan banner
**"Versi baru tersedia → Update sekarang"** (hanya untuk superadmin).

**Mau tes installer tanpa merilis?** Buka tab Actions → *Release installer* →
*Run workflow*. Hasilnya bisa diunduh sebagai artifact workflow, tidak dipublish,
dan tidak memicu notifikasi update ke user.

### Aturan supaya update aman di PC user

| Aturan | Alasan |
|---|---|
| Tag selalu `vMAJOR.MINOR.PATCH` | Dipakai untuk membandingkan versi saat cek update |
| Migration hanya **menambah**, jangan mengedit migration yang sudah dirilis | Di PC user migration lama sudah pernah dijalankan |
| Migration harus jalan di SQLite: pakai Schema Builder (`->change()`), jangan `ALTER TABLE ... MODIFY` atau raw SQL khusus MySQL | Update gagal kalau migration error |
| Sebelum membuat tag, tes dengan `DB_CONNECTION=sqlite php artisan migrate:fresh --seed` | |
| Seeder yang dijalankan saat update hanya yang ada di `DesktopUpgradeSeeder`, dan harus idempotent | `TikTokGiftSeeder` menghapus semua gift lebih dulu, sehingga mapping milik user ikut hilang |

## Cara kerjanya

```
%LOCALAPPDATA%\Programs\GiftTokTok\   <- ditimpa setiap update
├── app\               Laravel (vendor, hasil build Vite, node_modules listener sudah ada)
├── runtime\php\       PHP 8.4 portable + php.ini sendiri
├── runtime\node\      node.exe portable
└── launcher\          GiftTokTok.ps1, env.template, ikon

%APPDATA%\GiftTokTok\                 <- data user, TIDAK disentuh saat update/uninstall
├── .env               dibuat otomatis saat pertama kali dibuka (APP_KEY, APP_PORT)
├── database.sqlite
├── storage\           upload, log, cache, session
├── listener.env       ditulis lewat tombol "Simpan ke Listener" di halaman Admin project
├── backups\           salinan database sebelum impor
└── run\               PID, launcher.log
```

- **PHP/Node yang sudah ada di PC user tidak dipakai dan tidak terganggu.** Launcher
  memanggil `runtime\php\php.exe` lewat path lengkap, mengatur `PHPRC` sendiri, dan
  installer tidak mengubah PATH.
- **Port**: saat pertama kali dibuka, launcher memilih port kosong mulai dari 8000 lalu
  menyimpannya ke `APP_PORT`, sehingga URL untuk OBS tidak berubah-ubah. Kalau nanti
  port itu bentrok, akan muncul pesan untuk mengganti `APP_PORT`/`APP_URL` di `.env`.
- **Laravel** membaca `.env` dan `storage` dari folder data lewat env
  `GIFTTOKTOK_DATA` (lihat [bootstrap/app.php](../bootstrap/app.php) dan
  [config/desktop.php](../config/desktop.php)). Tanpa env itu (development biasa)
  semuanya berjalan seperti sebelumnya.
- **Launcher** ([launcher/GiftTokTok.ps1](launcher/GiftTokTok.ps1)), setiap kali dibuka:
  impor data kalau ada → `migrate --force` → seeder (lengkap saat instalasi pertama,
  `DesktopUpgradeSeeder` saat versi berubah) → `storage:link` → web server + queue
  worker → buka browser.
- **Update**: tombol "Update sekarang" mengunduh `.exe` dari GitHub Releases (checksum
  SHA-256 dicek) lalu menjalankannya dengan `/VERYSILENT`. Installer menghentikan
  GiftTokTok, menimpa folder aplikasi, lalu membukanya lagi.

### Shortcut yang dibuat installer

| Shortcut | Fungsi |
|---|---|
| GiftTokTok | Menjalankan server dan membuka browser |
| GiftTokTok Listener | Menjalankan TikTok gift listener (ada jendela log) |
| Hentikan GiftTokTok | Menghentikan semua proses |
| Folder Data GiftTokTok | Membuka `%APPDATA%\GiftTokTok` |

Login awal setelah instalasi baru: akun dari `UserSeeder` (password `password`).
**Ganti password-nya setelah login.**

## Pindah data dari instalasi lama (MySQL)

1. Di PC lama (yang masih memakai MySQL), dari folder project:
   ```bash
   php artisan migrate
   php artisan gifttoktok:export-desktop --output=C:\gifttoktok-export
   ```
2. Copy **isi** folder `gifttoktok-export` (`database.sqlite` + `storage\`) ke PC baru,
   di `%APPDATA%\GiftTokTok\import\`.
3. Buka GiftTokTok. Launcher otomatis mengimpornya (database lama di-backup dulu ke
   `backups\`), lalu folder `import` diganti nama menjadi `import-done-<waktu>`.

## Build manual (tanpa GitHub Actions)

Di PC Windows yang punya PHP 8.4, Composer, Node 22, dan Git:

```powershell
pwsh installer/build.ps1 -Version 1.2.0
# hasil: installer/build/output/GiftTokTok-Setup-1.2.0.exe
```

Inno Setup otomatis di-install lewat Chocolatey kalau belum ada.

## Catatan

- Installer belum di-sign, jadi Windows SmartScreen akan menampilkan "Windows protected
  your PC" → user perlu klik *More info → Run anyway*. Untuk menghilangkannya dibutuhkan
  sertifikat code signing.
