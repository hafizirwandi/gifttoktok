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

echo.
echo Service jalan di jendela baru - log live tampil di situ, sama seperti npm start.
echo JANGAN tutup jendela log itu selama live berlangsung.
echo Untuk berhenti, jalankan stop.bat ^(atau tutup langsung jendela log-nya^).
echo.
pause
