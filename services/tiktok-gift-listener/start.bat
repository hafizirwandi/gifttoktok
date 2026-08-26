@echo off
setlocal enabledelayedexpansion
cd /d "%~dp0"

if not exist node_modules (
    echo Menginstall dependencies, tunggu sebentar...
    call npm install
    if errorlevel 1 (
        echo Gagal npm install.
        pause
        exit /b 1
    )
)

if not exist .env (
    echo File .env belum ada.
    echo Copy .env.example jadi .env, isi 4 nilai dari halaman Admin project, lalu jalankan lagi.
    pause
    exit /b 1
)

REM Bersihkan cache config Laravel yang basi (bootstrap/cache/config.php) SEBELUM
REM apa pun lain jalan - inilah penyebab error "No application encryption key has
REM been specified" / popup 500 tiba-tiba yang muncul kalau .env sempat berubah
REM setelah cache config sempat dibuat (manual "php artisan optimize"/"config:cache"
REM di masa lalu, atau job terjadwal). Dijalankan setiap start.bat dipanggil supaya
REM config yang dipakai Apache/CLI SELALU baca .env terbaru, bukan cache basi.
echo Membersihkan cache config Laravel yang mungkin basi...
pushd "%~dp0..\.."
call php artisan config:clear >nul 2>&1
popd

REM Cek listener.pid BENERAN masih proses node.exe yang hidup, bukan cuma
REM percaya file-nya ada - PID basi (proses sudah mati/PC restart/ditutup
REM manual) adalah penyebab utama start.bat "kadang gagal" (nolak jalan
REM padahal sebenarnya tidak ada yang jalan).
if exist listener.pid (
    set "OLD_PID="
    set /p OLD_PID=<listener.pid
    if not "!OLD_PID!"=="" (
        tasklist /FI "PID eq !OLD_PID!" /FI "IMAGENAME eq node.exe" 2>nul | findstr /I "node.exe" >nul
        if not errorlevel 1 (
            echo Service sudah jalan ^(PID !OLD_PID!^).
            echo Jalankan stop.bat dulu kalau mau restart.
            pause
            exit /b 1
        )
        echo listener.pid lama ditemukan tapi PID !OLD_PID! sudah tidak jalan ^(basi^), dibersihkan otomatis.
    )
    del listener.pid
)

echo Membuka jendela log TikTok Gift Listener...
REM Sengaja TIDAK -WindowStyle Hidden dan TIDAK redirect ke file lagi - biar
REM jendela baru ini nampilin log live persis kayak jalanin "npm start"
REM manual, jadi begitu ada error langsung kelihatan di jendelanya.
powershell -NoProfile -Command "$p = Start-Process -FilePath 'node' -ArgumentList 'src\index.js' -WorkingDirectory '%~dp0' -PassThru; $p.Id | Out-File -Encoding ascii 'listener.pid'"

if not exist listener.pid (
    echo Gagal menjalankan service. Pastikan Node.js sudah terinstall ^(cek: node -v^).
    pause
    exit /b 1
)

REM Queue worker Laravel (php artisan queue:work) - proses "Event Trigger" tipe
REM like/chat yang diqueue (lihat App\Jobs\ProcessTikTokEventTrigger), supaya user
REM tidak perlu buka terminal terpisah utk itu. Pola PID-nya sama persis dgn
REM listener.pid di atas, cuma file & IMAGENAME beda (php.exe, bukan node.exe).
REM Pakai flag variable (bukan goto) buat lompat keluar blok if bersarang - goto
REM dari dalam if(...) yang nested rawan bikin cmd.exe error "was unexpected at
REM this time" begitu keluar dari beberapa level tanda kurung sekaligus.
set "QUEUE_ALREADY_RUNNING=0"

if exist queue-worker.pid (
    set "OLD_QUEUE_PID="
    set /p OLD_QUEUE_PID=<queue-worker.pid
    if not "!OLD_QUEUE_PID!"=="" (
        tasklist /FI "PID eq !OLD_QUEUE_PID!" /FI "IMAGENAME eq php.exe" 2>nul | findstr /I "php.exe" >nul
        if not errorlevel 1 (
            echo Queue worker sudah jalan ^(PID !OLD_QUEUE_PID!^), tidak dijalankan ulang.
            set "QUEUE_ALREADY_RUNNING=1"
        ) else (
            echo queue-worker.pid lama ditemukan tapi PID !OLD_QUEUE_PID! sudah tidak jalan ^(basi^), dibersihkan otomatis.
        )
    )
    if "!QUEUE_ALREADY_RUNNING!"=="0" del queue-worker.pid
)

if "!QUEUE_ALREADY_RUNNING!"=="0" (
    echo Menjalankan queue worker Laravel ^(proses Event Trigger like/chat^) di background...
    powershell -NoProfile -Command "$p = Start-Process -FilePath 'php' -ArgumentList 'artisan queue:work --queue=default --sleep=1 --tries=3' -WorkingDirectory '%~dp0..\..\' -WindowStyle Hidden -PassThru; $p.Id | Out-File -Encoding ascii 'queue-worker.pid'"

    if not exist queue-worker.pid (
        echo Gagal menjalankan queue worker. Pastikan PHP ada di PATH ^(cek: php -v^) - fitur Event Trigger like/chat tidak akan berjalan tanpa ini.
    ) else (
        echo Queue worker jalan di background ^(tersembunyi, tanpa jendela - errornya masuk storage\logs\laravel.log^).
    )
)

echo.
echo Service jalan di jendela baru - log live tampil di situ, sama seperti npm start.
echo JANGAN tutup jendela log itu selama live berlangsung.
echo Untuk berhenti, jalankan stop.bat ^(atau tutup langsung jendela log-nya^).
echo.
pause
