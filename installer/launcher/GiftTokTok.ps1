# GiftTokTok launcher (versi desktop / installer Windows).
#
# Dipanggil dari shortcut yang dibuat installer (installer/gifttoktok.iss):
#   GiftTokTok.ps1             -> siapkan data, jalankan web server + queue worker, buka browser
#   GiftTokTok.ps1 -Listener   -> sama, lalu jalankan TikTok gift listener (jendela log)
#   GiftTokTok.ps1 -Stop       -> hentikan semua proses GiftTokTok (dipakai juga oleh installer
#                                 sebelum update/uninstall)
#
# Struktur instalasi:  {app}\app (Laravel), {app}\runtime\php, {app}\runtime\node, {app}\launcher
# Data per-user:       %APPDATA%\GiftTokTok (.env, database.sqlite, storage\, listener.env, run\)
#
# CATATAN: file ini sengaja ASCII saja - Windows PowerShell 5.1 membaca .ps1 tanpa BOM
# sebagai ANSI, karakter non-ASCII bisa rusak.

param(
    [switch]$Stop,
    [switch]$Listener,
    [switch]$NoBrowser
)

$ErrorActionPreference = 'Stop'

$AppRoot   = Split-Path -Parent $PSScriptRoot
$AppDir    = Join-Path $AppRoot 'app'
$PhpDir    = Join-Path $AppRoot 'runtime\php'
$Php       = Join-Path $PhpDir 'php.exe'
$Node      = Join-Path $AppRoot 'runtime\node\node.exe'
$DataDir   = Join-Path $env:APPDATA 'GiftTokTok'
$RunDir    = Join-Path $DataDir 'run'
$LogFile   = Join-Path $RunDir 'launcher.log'
$EnvFile   = Join-Path $DataDir '.env'
$DbFile    = Join-Path $DataDir 'database.sqlite'
$ListenerEnv = Join-Path $DataDir 'listener.env'
$ImportDir = Join-Path $DataDir 'import'
$StorageLink = Join-Path $AppDir 'public\storage'

# Isolasi total dari PHP/Node yang mungkin sudah terpasang di PC user (XAMPP, Laragon,
# nvm, dll): pakai php.ini milik kita sendiri & abaikan PHPRC/PHP_INI_SCAN_DIR global.
# Path absolut (extension_dir, CA bundle) ditulis ke ini per-run di folder data supaya
# tidak perlu quoting argumen command-line (folder user bisa mengandung spasi).
$env:GIFTTOKTOK_DATA = $DataDir
$env:PHPRC = $PhpDir
$env:PHP_INI_SCAN_DIR = Join-Path $RunDir 'php-ini'
$env:LISTENER_ENV_FILE = $ListenerEnv

# GIFTTOKTOK_HEADLESS=1: tanpa dialog/tray (smoke test di GitHub Actions, build.ps1).
$Headless = [bool]$env:GIFTTOKTOK_HEADLESS
if (-not $Headless) {
    Add-Type -AssemblyName System.Windows.Forms
    Add-Type -AssemblyName System.Drawing
}

# ---------------------------------------------------------------------------- helpers

function Write-Log([string]$Message) {
    try {
        Add-Content -Path $LogFile -Value ("[{0}] {1}" -f (Get-Date -Format 'yyyy-MM-dd HH:mm:ss'), $Message)
    } catch { }
}

function Show-Error([string]$Message) {
    Write-Log "ERROR: $Message"
    if ($Headless) { [Console]::Error.WriteLine($Message); return }
    [System.Windows.Forms.MessageBox]::Show(
        "$Message`r`n`r`nLog: $LogFile",
        'GiftTokTok',
        [System.Windows.Forms.MessageBoxButtons]::OK,
        [System.Windows.Forms.MessageBoxIcon]::Error
    ) | Out-Null
}

function Show-Info([string]$Message) {
    if ($Headless) { Write-Host $Message; return }
    [System.Windows.Forms.MessageBox]::Show(
        $Message,
        'GiftTokTok',
        [System.Windows.Forms.MessageBoxButtons]::OK,
        [System.Windows.Forms.MessageBoxIcon]::Information
    ) | Out-Null
}

$script:Tray = $null
function Show-Progress([string]$Message) {
    Write-Log $Message
    if ($Headless) { Write-Host $Message; return }
    if (-not $script:Tray) {
        $script:Tray = New-Object System.Windows.Forms.NotifyIcon
        $icon = Join-Path $PSScriptRoot 'gifttoktok.ico'
        $script:Tray.Icon = if (Test-Path $icon) { New-Object System.Drawing.Icon $icon } else { [System.Drawing.SystemIcons]::Information }
        $script:Tray.Text = 'GiftTokTok'
        $script:Tray.Visible = $true
    }
    $script:Tray.ShowBalloonTip(5000, 'GiftTokTok', $Message, [System.Windows.Forms.ToolTipIcon]::Info)
}

function Hide-Progress {
    if ($script:Tray) {
        $script:Tray.Visible = $false
        $script:Tray.Dispose()
        $script:Tray = $null
    }
}

function Invoke-Artisan([string[]]$ArtisanArgs) {
    # EAP lokal 'Continue': di PS 5.1, stderr dari exe native + 2>&1 dengan EAP 'Stop'
    # langsung dianggap exception walau exit code 0 (mis. warning PHP biasa).
    $ErrorActionPreference = 'Continue'
    Push-Location $AppDir
    try {
        $output = & $Php artisan @ArtisanArgs --no-interaction 2>&1 | Out-String
        $code = $LASTEXITCODE
    } finally {
        Pop-Location
    }
    Write-Log ("artisan {0} (exit {1})`r`n{2}" -f ($ArtisanArgs -join ' '), $code, $output.Trim())
    if ($code -ne 0) {
        throw ("php artisan {0} gagal:`r`n{1}" -f ($ArtisanArgs -join ' '), $output.Trim())
    }
}

function Get-OwnProcess([string]$Name) {
    $pidFile = Join-Path $RunDir "$Name.pid"
    if (-not (Test-Path $pidFile)) { return $null }
    $procId = (Get-Content $pidFile -ErrorAction SilentlyContinue | Select-Object -First 1)
    if (-not $procId) { return $null }
    $proc = Get-Process -Id ([int]$procId) -ErrorAction SilentlyContinue
    # Cocokkan path exe - PID bisa dipakai ulang Windows oleh proses lain setelah restart.
    if ($proc -and $proc.Path -and $proc.Path.StartsWith($AppRoot, [System.StringComparison]::OrdinalIgnoreCase)) {
        return $proc
    }
    Remove-Item $pidFile -ErrorAction SilentlyContinue
    return $null
}

function Start-Background([string]$Name, [string]$File, [string]$Arguments, [string]$WorkDir, [switch]$Visible) {
    $style = if ($Visible) { 'Normal' } else { 'Hidden' }
    $proc = Start-Process -FilePath $File -ArgumentList $Arguments -WorkingDirectory $WorkDir -WindowStyle $style -PassThru
    Set-Content -Path (Join-Path $RunDir "$Name.pid") -Value $proc.Id -Encoding ASCII
    Write-Log "Started $Name (PID $($proc.Id)): $File $Arguments"
    return $proc
}

function Test-PortFree([int]$Port) {
    # 1) Ada yang sudah listen (termasuk di 0.0.0.0)? -> koneksi berhasil = terpakai.
    $client = New-Object System.Net.Sockets.TcpClient
    try {
        $async = $client.BeginConnect('127.0.0.1', $Port, $null, $null)
        if ($async.AsyncWaitHandle.WaitOne(300) -and $client.Connected) { return $false }
    } catch { } finally { $client.Close() }

    # 2) Bisa di-bind?
    $listener = New-Object System.Net.Sockets.TcpListener -ArgumentList ([System.Net.IPAddress]::Loopback), $Port
    try {
        $listener.Start()
        $listener.Stop()
        return $true
    } catch {
        return $false
    }
}

function Get-EnvValue([string]$Key) {
    if (-not (Test-Path $EnvFile)) { return $null }
    $line = Get-Content $EnvFile | Where-Object { $_ -match "^\s*$Key\s*=" } | Select-Object -First 1
    if (-not $line) { return $null }
    return ($line -replace "^\s*$Key\s*=\s*", '').Trim().Trim('"')
}

function Remove-StorageLink {
    # rmdir pada junction hanya menghapus link-nya, BUKAN isi folder tujuan (upload user).
    if (Test-Path $StorageLink) {
        cmd /c rmdir "$StorageLink" 2>$null | Out-Null
    }
}

function Stop-All {
    foreach ($name in 'listener', 'queue', 'web') {
        $proc = Get-OwnProcess $name
        if ($proc) {
            Stop-Process -Id $proc.Id -Force -ErrorAction SilentlyContinue
            Write-Log "Stopped $name (PID $($proc.Id))"
        }
        Remove-Item (Join-Path $RunDir "$name.pid") -ErrorAction SilentlyContinue
    }
    # Sisa proses yatim dari runtime KITA SAJA (path di bawah folder instalasi) - PHP/Node
    # lain milik user (XAMPP dll) tidak disentuh.
    Get-Process -Name php, node -ErrorAction SilentlyContinue |
        Where-Object { $_.Path -and $_.Path.StartsWith($AppRoot, [System.StringComparison]::OrdinalIgnoreCase) } |
        ForEach-Object {
            Stop-Process -Id $_.Id -Force -ErrorAction SilentlyContinue
            Write-Log "Stopped orphan $($_.Name) (PID $($_.Id))"
        }
    Remove-StorageLink
}

function Wait-Http([string]$Url, [int]$Seconds = 30) {
    $deadline = (Get-Date).AddSeconds($Seconds)
    while ((Get-Date) -lt $deadline) {
        try {
            $res = Invoke-WebRequest -Uri $Url -UseBasicParsing -TimeoutSec 3
            if ($res.StatusCode -lt 500) { return $true }
        } catch { }
        Start-Sleep -Milliseconds 500
    }
    return $false
}

# ------------------------------------------------------------------------ setup steps

function Initialize-DataDir {
    $dirs = @(
        $DataDir, $RunDir, $env:PHP_INI_SCAN_DIR,
        (Join-Path $DataDir 'backups'),
        (Join-Path $DataDir 'storage\app\public'),
        (Join-Path $DataDir 'storage\app\private'),
        (Join-Path $DataDir 'storage\framework\cache\data'),
        (Join-Path $DataDir 'storage\framework\sessions'),
        (Join-Path $DataDir 'storage\framework\views'),
        (Join-Path $DataDir 'storage\logs')
    )
    foreach ($dir in $dirs) {
        if (-not (Test-Path $dir)) { New-Item -ItemType Directory -Path $dir -Force | Out-Null }
    }

    # Path absolut utk PHP - ditulis ulang tiap run (folder instalasi bisa dipindah).
    $ext = (Join-Path $PhpDir 'ext').Replace('\', '/')
    $ca  = (Join-Path $PhpDir 'cacert.pem').Replace('\', '/')
    Set-Content -Path (Join-Path $env:PHP_INI_SCAN_DIR 'gifttoktok-paths.ini') -Encoding ASCII -Value @(
        "extension_dir = `"$ext`"",
        "curl.cainfo = `"$ca`"",
        "openssl.cafile = `"$ca`""
    )
}

function Initialize-EnvFile {
    if (Test-Path $EnvFile) { return }

    $port = 8000
    while (-not (Test-PortFree $port) -and $port -lt 8100) { $port++ }

    $template = Get-Content (Join-Path $PSScriptRoot 'env.template') -Raw
    $content = $template.Replace('__APP_PORT__', [string]$port)
    # UTF-8 TANPA BOM - phpdotenv gagal parse baris pertama kalau ada BOM.
    [System.IO.File]::WriteAllText($EnvFile, $content, (New-Object System.Text.UTF8Encoding $false))
    Write-Log "Created $EnvFile (port $port)"

    Invoke-Artisan @('key:generate', '--force')
}

function Import-PendingData {
    # Paket hasil "php artisan gifttoktok:export-desktop" yang di-copy user ke
    # %APPDATA%\GiftTokTok\import (lihat installer/README.md).
    $importDb = Join-Path $ImportDir 'database.sqlite'
    if (-not (Test-Path $importDb)) { return $false }

    Show-Progress 'Mengimpor data dari folder import...'
    $stamp = Get-Date -Format 'yyyyMMdd-HHmmss'

    if (Test-Path $DbFile) {
        Copy-Item $DbFile (Join-Path $DataDir "backups\database-$stamp.sqlite")
    }
    foreach ($suffix in '', '-wal', '-shm') {
        Remove-Item "$DbFile$suffix" -ErrorAction SilentlyContinue
    }
    Copy-Item $importDb $DbFile

    $importStorage = Join-Path $ImportDir 'storage'
    if (Test-Path $importStorage) {
        robocopy $importStorage (Join-Path $DataDir 'storage') /E /NFL /NDL /NJH /NJS /NP | Out-Null
    }

    Rename-Item $ImportDir "import-done-$stamp"
    Write-Log "Imported data from $ImportDir"
    return $true
}

function Update-Database([bool]$FreshDatabase) {
    Invoke-Artisan @('migrate', '--force')

    $versionFile = Join-Path $AppDir 'VERSION'
    $appVersion = if (Test-Path $versionFile) { (Get-Content $versionFile -Raw).Trim() } else { 'dev' }
    $seededFile = Join-Path $RunDir 'seeded-version.txt'
    $seededVersion = if (Test-Path $seededFile) { (Get-Content $seededFile -Raw).Trim() } else { '' }

    if ($FreshDatabase) {
        Show-Progress 'Mengisi data awal (katalog gift TikTok, akun admin)...'
        Invoke-Artisan @('db:seed', '--force')
    } elseif ($seededVersion -ne $appVersion) {
        Invoke-Artisan @('db:seed', '--class=Database\Seeders\DesktopUpgradeSeeder', '--force')
    }

    if ($seededVersion -ne $appVersion) {
        # Cache view Blade lama & hasil cek update (versi sudah berubah).
        Invoke-Artisan @('view:clear')
        Invoke-Artisan @('cache:clear')
        Set-Content -Path $seededFile -Value $appVersion -Encoding ASCII
    }
}

function Start-WebServer([int]$Port) {
    if (Get-OwnProcess 'web') { return }

    if (-not (Test-PortFree $Port)) {
        throw ("Port $Port sedang dipakai aplikasi lain.`r`n`r`n" +
            "Tutup aplikasi yang memakai port itu, atau ganti APP_PORT (dan APP_URL) di:`r`n$EnvFile")
    }

    if (-not (Test-Path $StorageLink)) {
        Invoke-Artisan @('storage:link')
    }

    # Langsung php -S + router Laravel (persis yang dipakai "artisan serve" di baliknya),
    # cwd = public\ karena server.php memakai getcwd() sebagai document root.
    Start-Background 'web' $Php "-S 127.0.0.1:$Port ..\vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php" (Join-Path $AppDir 'public') | Out-Null

    $ok = Wait-Http "http://127.0.0.1:$Port/up" 30
    if (-not (Get-OwnProcess 'web')) {
        throw "Web server langsung berhenti - kemungkinan port $Port dipakai aplikasi lain. Ganti APP_PORT & APP_URL di:`r`n$EnvFile"
    }
    if (-not $ok) {
        throw "Web server tidak merespons di port $Port."
    }
}

function Start-QueueWorker {
    if (Get-OwnProcess 'queue') { return }
    # Memproses Event Trigger like/chat (App\Jobs\ProcessTikTokEventTrigger).
    Start-Background 'queue' $Php 'artisan queue:work --queue=default --sleep=1 --tries=3' $AppDir | Out-Null
}

function Start-Listener([int]$Port) {
    if (Get-OwnProcess 'listener') {
        Show-Info 'TikTok listener sudah berjalan (lihat jendela log-nya di taskbar).'
        return
    }
    if (-not (Test-Path $ListenerEnv)) {
        Show-Info ("Listener belum dikonfigurasi.`r`n`r`n" +
            "Buka halaman Admin project di GiftTokTok, aktifkan Auto Gift Mode, isi username TikTok, " +
            "lalu klik tombol 'Simpan ke Listener'. Setelah itu buka shortcut 'GiftTokTok Listener' lagi.")
        Start-Process "http://127.0.0.1:$Port/project-live"
        return
    }
    # Sengaja jendela TERLIHAT - log live (status koneksi, gift masuk, error) tampil di situ.
    Start-Background 'listener' $Node 'src\index.js' (Join-Path $AppDir 'services\tiktok-gift-listener') -Visible | Out-Null
}

# ------------------------------------------------------------------------------- main

# Satu launcher saja dalam satu waktu (user sering double-click berkali-kali saat
# first-run masih jalan).
$mutex = New-Object System.Threading.Mutex($false, 'Local\GiftTokTokLauncher')
if (-not $mutex.WaitOne(0)) {
    if (-not $Stop) { Show-Info 'GiftTokTok sedang disiapkan, tunggu sebentar...' }
    exit 0
}

try {
    if (-not (Test-Path $RunDir)) { New-Item -ItemType Directory -Path $RunDir -Force | Out-Null }

    if ($Stop) {
        Stop-All
        exit 0
    }

    if (-not (Test-Path $Php)) {
        throw "Runtime PHP tidak ditemukan di $Php. Install ulang GiftTokTok."
    }

    Initialize-DataDir

    $alreadyRunning = [bool](Get-OwnProcess 'web')

    if (-not $alreadyRunning) {
        $firstRun = -not (Test-Path $EnvFile)
        if ($firstRun) { Show-Progress 'Menyiapkan GiftTokTok untuk pertama kali, tunggu sebentar...' }

        Initialize-EnvFile

        # Import hanya saat server mati (file database tidak sedang dibuka).
        Stop-All
        $imported = Import-PendingData
        $freshDatabase = -not (Test-Path $DbFile)
        if ($freshDatabase) { New-Item -ItemType File -Path $DbFile | Out-Null }

        Update-Database ($freshDatabase -and -not $imported)
    }

    $port = [int](Get-EnvValue 'APP_PORT')
    if (-not $port) { $port = 8000 }

    if (-not $alreadyRunning) {
        Show-Progress 'Menjalankan GiftTokTok...'
        Start-WebServer $port
    }
    Start-QueueWorker

    if ($Listener) {
        Start-Listener $port
    } elseif (-not $NoBrowser) {
        Start-Process "http://127.0.0.1:$port"
    }
} catch {
    Show-Error $_.Exception.Message
    exit 1
} finally {
    Hide-Progress
    $mutex.ReleaseMutex()
}
