@echo off
setlocal
cd /d "%~dp0"

if not exist listener.pid (
    echo Tidak ada service yang sedang jalan ^(listener.pid tidak ditemukan^).
    pause
    exit /b 0
)

set /p NODE_PID=<listener.pid

taskkill /PID %NODE_PID% /T /F >nul 2>&1

del listener.pid

echo Service dihentikan.
pause
