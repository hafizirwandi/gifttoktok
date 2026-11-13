<?php

/*
|--------------------------------------------------------------------------
| Mode desktop (installer Windows)
|--------------------------------------------------------------------------
|
| Aktif otomatis kalau aplikasi dijalankan lewat launcher installer (env
| GIFTTOKTOK_DATA diset ke folder data per-user). Lihat installer/README.md.
|
*/

$dataPath = getenv('GIFTTOKTOK_DATA') ?: null;
$versionFile = base_path('VERSION');

return [

    'enabled' => $dataPath !== null,

    // %APPDATA%\GiftTokTok - berisi .env, database.sqlite, storage\, listener.env, run\.
    'data_path' => $dataPath,

    // Ditulis oleh installer/build.ps1 dari tag git (v1.2.0 -> 1.2.0). Tidak ada di
    // repo/development, jadi 'dev' - cek update otomatis hanya jalan di mode desktop.
    'version' => is_file($versionFile) ? trim((string) file_get_contents($versionFile)) : 'dev',

    // Repo GitHub publik tempat GitHub Actions meng-upload GiftTokTok-Setup-x.y.z.exe.
    'github_repo' => env('DESKTOP_UPDATE_REPO', 'hafizirwandi/gifttoktok'),

    // Lama cache hasil cek versi terbaru ke GitHub API (detik).
    'update_check_ttl' => 6 * 60 * 60,

];
