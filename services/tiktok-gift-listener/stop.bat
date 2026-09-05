@echo off
setlocal enabledelayedexpansion
cd /d "%~dp0"

REM Node gift-listener dan queue worker Laravel dicek & dimatikan independen -
REM kalau salah satu sudah mati duluan (atau PID-nya basi), itu TIDAK menghalangi
REM yang lain tetap diproses (beda dari versi lama yang langsung exit di kegagalan
REM pertama). Pakai flag variable (bukan goto) buat lompat keluar blok if
REM bersarang - goto dari dalam if(...) yang nested rawan bikin cmd.exe error
REM "was unexpected at this time" begitu keluar dari beberapa level tanda kurung.

set "SKIP_LISTENER=0"

if not exist listener.pid (
    echo [Listener] Tidak ada service yang sedang jalan ^(listener.pid tidak ditemukan^).
    set "SKIP_LISTENER=1"
)

if "!SKIP_LISTENER!"=="0" (
    set "NODE_PID="
    set /p NODE_PID=<listener.pid

    if "!NODE_PID!"=="" (
        echo [Listener] listener.pid kosong/rusak - dihapus, tidak ada proses yang dihentikan.
        del listener.pid
        set "SKIP_LISTENER=1"
    )
)

if "!SKIP_LISTENER!"=="0" (
    REM Verifikasi dulu PID itu beneran node.exe yang masih hidup sebelum di-kill -
    REM Windows suka nge-reuse nomor PID, jadi taskkill buta bisa salah bunuh
    REM proses lain yang tidak ada hubungannya kalau langsung percaya listener.pid.
    tasklist /FI "PID eq !NODE_PID!" /FI "IMAGENAME eq node.exe" 2>nul | findstr /I "node.exe" >nul
    if errorlevel 1 (
        echo [Listener] PID !NODE_PID! di listener.pid bukan proses node.exe yang sedang jalan ^(kemungkinan sudah mati sebelumnya^).
        echo [Listener] listener.pid dihapus, tidak ada proses yang dihentikan paksa.
        del listener.pid
        set "SKIP_LISTENER=1"
    )
)

if "!SKIP_LISTENER!"=="0" (
    taskkill /PID !NODE_PID! /T /F
    if errorlevel 1 (
        echo [Listener] Gagal menghentikan proses PID !NODE_PID!. Coba tutup manual jendela log-nya.
    ) else (
        del listener.pid
        echo [Listener] Service dihentikan ^(PID !NODE_PID!^).
    )
)

set "SKIP_QUEUE=0"

if not exist queue-worker.pid (
    echo [Queue Worker] Tidak ada queue worker yang sedang jalan ^(queue-worker.pid tidak ditemukan^).
    set "SKIP_QUEUE=1"
)

if "!SKIP_QUEUE!"=="0" (
    set "QUEUE_PID="
    set /p QUEUE_PID=<queue-worker.pid

    if "!QUEUE_PID!"=="" (
        echo [Queue Worker] queue-worker.pid kosong/rusak - dihapus, tidak ada proses yang dihentikan.
        del queue-worker.pid
        set "SKIP_QUEUE=1"
    )
)

if "!SKIP_QUEUE!"=="0" (
    tasklist /FI "PID eq !QUEUE_PID!" /FI "IMAGENAME eq php.exe" 2>nul | findstr /I "php.exe" >nul
    if errorlevel 1 (
        echo [Queue Worker] PID !QUEUE_PID! di queue-worker.pid bukan proses php.exe yang sedang jalan ^(kemungkinan sudah mati sebelumnya^).
        echo [Queue Worker] queue-worker.pid dihapus, tidak ada proses yang dihentikan paksa.
        del queue-worker.pid
        set "SKIP_QUEUE=1"
    )
)

if "!SKIP_QUEUE!"=="0" (
    taskkill /PID !QUEUE_PID! /T /F
    if errorlevel 1 (
        echo [Queue Worker] Gagal menghentikan proses PID !QUEUE_PID!.
    ) else (
        del queue-worker.pid
        echo [Queue Worker] Dihentikan ^(PID !QUEUE_PID!^).
    )
)

pause
