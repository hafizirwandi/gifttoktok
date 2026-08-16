@echo off
setlocal
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

if exist listener.pid (
    echo Service sepertinya sudah jalan ^(listener.pid ada^).
    echo Jalankan stop.bat dulu kalau mau restart.
    pause
    exit /b 1
)

echo Menjalankan TikTok Gift Listener di background...
powershell -NoProfile -Command "$p = Start-Process -FilePath 'node' -ArgumentList 'src\index.js' -WorkingDirectory '%~dp0' -RedirectStandardOutput 'listener.log' -RedirectStandardError 'listener.err.log' -WindowStyle Hidden -PassThru; $p.Id | Out-File -Encoding ascii 'listener.pid'"

if not exist listener.pid (
    echo Gagal menjalankan service. Pastikan Node.js sudah terinstall ^(cek: node -v^).
    pause
    exit /b 1
)

echo.
echo Service jalan di background. Log ada di listener.log
echo Untuk berhenti, jalankan stop.bat
echo.
pause
