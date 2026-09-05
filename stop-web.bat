@echo off
setlocal enabledelayedexpansion
cd /d "%~dp0"

set "SKIP_WEB=0"

if not exist web-server.pid (
    echo Tidak ada web server yang sedang jalan ^(web-server.pid tidak ditemukan^).
    set "SKIP_WEB=1"
)

if "!SKIP_WEB!"=="0" (
    set "WEB_PID="
    set /p WEB_PID=<web-server.pid

    if "!WEB_PID!"=="" (
        echo web-server.pid kosong/rusak - dihapus, tidak ada proses yang dihentikan.
        del web-server.pid
        set "SKIP_WEB=1"
    )
)

if "!SKIP_WEB!"=="0" (
    REM Verifikasi dulu PID itu beneran php.exe yang masih hidup sebelum di-kill -
    REM Windows suka nge-reuse nomor PID, jadi taskkill buta bisa salah bunuh
    REM proses lain yang tidak ada hubungannya kalau langsung percaya web-server.pid.
    tasklist /FI "PID eq !WEB_PID!" /FI "IMAGENAME eq php.exe" 2>nul | findstr /I "php.exe" >nul
    if errorlevel 1 (
        echo PID !WEB_PID! di web-server.pid bukan proses php.exe yang sedang jalan ^(kemungkinan sudah mati sebelumnya^).
        echo web-server.pid dihapus, tidak ada proses yang dihentikan paksa.
        del web-server.pid
        set "SKIP_WEB=1"
    )
)

if "!SKIP_WEB!"=="0" (
    taskkill /PID !WEB_PID! /T /F
    if errorlevel 1 (
        echo Gagal menghentikan proses PID !WEB_PID!.
    ) else (
        del web-server.pid
        echo Web server dihentikan ^(PID !WEB_PID!^).
    )
)

pause
