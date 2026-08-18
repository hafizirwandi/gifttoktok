@echo off
setlocal enabledelayedexpansion
cd /d "%~dp0"

if not exist listener.pid (
    echo Tidak ada service yang sedang jalan ^(listener.pid tidak ditemukan^).
    pause
    exit /b 0
)

set "NODE_PID="
set /p NODE_PID=<listener.pid

if "!NODE_PID!"=="" (
    echo listener.pid kosong/rusak - dihapus, tidak ada proses yang dihentikan.
    del listener.pid
    pause
    exit /b 0
)

REM Verifikasi dulu PID itu beneran node.exe yang masih hidup sebelum di-kill -
REM Windows suka nge-reuse nomor PID, jadi taskkill buta bisa salah bunuh
REM proses lain yang tidak ada hubungannya kalau langsung percaya listener.pid.
tasklist /FI "PID eq !NODE_PID!" /FI "IMAGENAME eq node.exe" 2>nul | findstr /I "node.exe" >nul
if errorlevel 1 (
    echo PID !NODE_PID! di listener.pid bukan proses node.exe yang sedang jalan ^(kemungkinan sudah mati sebelumnya^).
    echo listener.pid dihapus, tidak ada proses yang dihentikan paksa.
    del listener.pid
    pause
    exit /b 0
)

taskkill /PID !NODE_PID! /T /F
if errorlevel 1 (
    echo Gagal menghentikan proses PID !NODE_PID!. Coba tutup manual jendela log-nya.
    pause
    exit /b 1
)

del listener.pid
echo Service dihentikan ^(PID !NODE_PID!^).
pause
