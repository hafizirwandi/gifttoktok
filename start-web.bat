@echo off
setlocal enabledelayedexpansion
cd /d "%~dp0"

REM Bersihkan cache config Laravel yang basi (bootstrap/cache/config.php) SEBELUM
REM apa pun lain jalan - ini bisa bikin .env "kelihatan" tidak kebaca (APP_ENV/
REM DB_CONNECTION/APP_KEY balik ke default Laravel) walau isinya sudah benar.
echo Membersihkan cache config Laravel yang mungkin basi...
call php artisan config:clear >nul 2>&1

REM Cek web-server.pid BENERAN masih proses php.exe yang hidup, bukan cuma
REM percaya file-nya ada - PID basi (proses sudah mati/PC restart/ditutup
REM manual) adalah penyebab utama script beginian "kadang gagal" (nolak jalan
REM padahal sebenarnya tidak ada yang jalan).
set "WEB_ALREADY_RUNNING=0"

if exist web-server.pid (
    set "OLD_WEB_PID="
    set /p OLD_WEB_PID=<web-server.pid
    if not "!OLD_WEB_PID!"=="" (
        tasklist /FI "PID eq !OLD_WEB_PID!" /FI "IMAGENAME eq php.exe" 2>nul | findstr /I "php.exe" >nul
        if not errorlevel 1 (
            echo Web server sudah jalan ^(PID !OLD_WEB_PID!^), tidak dijalankan ulang.
            set "WEB_ALREADY_RUNNING=1"
        ) else (
            echo web-server.pid lama ditemukan tapi PID !OLD_WEB_PID! sudah tidak jalan ^(basi^), dibersihkan otomatis.
        )
    )
    if "!WEB_ALREADY_RUNNING!"=="0" del web-server.pid
)

if "!WEB_ALREADY_RUNNING!"=="0" (
    echo Menjalankan web server Laravel ^(php artisan serve^) di background...
    powershell -NoProfile -Command "$p = Start-Process -FilePath 'php' -ArgumentList 'artisan serve' -WorkingDirectory '%~dp0' -WindowStyle Hidden -PassThru; $p.Id | Out-File -Encoding ascii 'web-server.pid'"

    if not exist web-server.pid (
        echo Gagal menjalankan web server. Pastikan PHP ada di PATH ^(cek: php -v^).
        pause
        exit /b 1
    )

    echo Web server jalan di background ^(tersembunyi, tanpa jendela - alamat http://127.0.0.1:8000^).
    REM Kasih waktu sebentar biar php artisan serve selesai boot sebelum Chrome dibuka.
    timeout /t 2 /nobreak >nul
)

echo Membuka GiftTokTok di Chrome...
start chrome "http://127.0.0.1:8000"

echo.
echo Web server jalan tersembunyi di background (tanpa jendela).
echo Untuk berhenti, jalankan stop-web.bat.
echo.
pause
