# Build installer GiftTokTok-Setup-<versi>.exe (Windows). Dipakai GitHub Actions
# (.github/workflows/release.yml), bisa juga dijalankan manual di PC Windows yang punya
# PHP 8.4 + Composer + Node 22 di PATH:
#
#   pwsh installer/build.ps1 -Version 1.2.0
#
# Hasil: installer/build/output/GiftTokTok-Setup-1.2.0.exe
#
# Layout installer/build/ = layout folder instalasi persis ({app}):
#   app\ (Laravel siap pakai)   runtime\php\   runtime\node\   launcher\

param(
    [Parameter(Mandatory = $true)][string]$Version,
    [string]$PhpBranch = '8.4',
    [int]$NodeMajor = 22,
    [switch]$SkipSmokeTest
)

$ErrorActionPreference = 'Stop'
$ProgressPreference = 'SilentlyContinue'   # Invoke-WebRequest jauh lebih cepat tanpa progress bar

if ($Version -notmatch '^\d+\.\d+\.\d+$') {
    throw "Versi harus format MAJOR.MINOR.PATCH (contoh 1.2.0), dapat: '$Version'"
}

$Repo    = Split-Path -Parent $PSScriptRoot
$Build   = Join-Path $PSScriptRoot 'build'
$Cache   = Join-Path $PSScriptRoot 'cache'
$AppDir  = Join-Path $Build 'app'
$PhpDir  = Join-Path $Build 'runtime\php'
$NodeDir = Join-Path $Build 'runtime\node'

function Step([string]$Message) { Write-Host "`n==> $Message" -ForegroundColor Cyan }

function Invoke-Native([string]$File, [string[]]$Arguments) {
    & $File @Arguments
    if ($LASTEXITCODE -ne 0) { throw "$File $($Arguments -join ' ') gagal (exit $LASTEXITCODE)" }
}

function Get-Download([string]$Url, [string]$OutFile, [string]$Sha256) {
    if (-not (Test-Path $OutFile)) {
        Write-Host "    download $Url"
        Invoke-WebRequest -Uri $Url -OutFile $OutFile -UseBasicParsing
    }
    if ($Sha256) {
        $actual = (Get-FileHash $OutFile -Algorithm SHA256).Hash
        if ($actual -ne $Sha256.ToUpper()) {
            Remove-Item $OutFile
            throw "Checksum $OutFile tidak cocok (dapat $actual, harusnya $Sha256)"
        }
    }
}

# --------------------------------------------------------------------------- 1. bersih
Step "Bersihkan $Build"
if (Test-Path $Build) {
    # Lepas junction sisa smoke test sebelumnya dulu (lihat launcher Remove-StorageLink).
    $link = Join-Path $AppDir 'public\storage'
    if (Test-Path $link) { cmd /c rmdir "$link" | Out-Null }
    Remove-Item $Build -Recurse -Force
}
New-Item -ItemType Directory -Force -Path $AppDir, $PhpDir, $NodeDir, $Cache | Out-Null

# ------------------------------------------------------------------------- 2. source
Step 'Ambil source dari git (hanya file yang di-commit)'
$tar = Join-Path $Cache 'source.tar'
Invoke-Native git @('-C', $Repo, 'archive', '--format=tar', '-o', $tar, 'HEAD')
Invoke-Native tar @('-xf', $tar, '-C', $AppDir)
Remove-Item $tar

# File khusus development / bukan untuk PC user.
$devOnly = @(
    '.github', 'installer', 'tests', 'phpunit.xml', '.editorconfig',
    'start-web.bat', 'stop-web.bat',
    'services\tiktok-gift-listener\start.bat', 'services\tiktok-gift-listener\stop.bat'
)
foreach ($item in $devOnly) {
    $path = Join-Path $AppDir $item
    if (Test-Path $path) { Remove-Item $path -Recurse -Force }
}

Set-Content -Path (Join-Path $AppDir 'VERSION') -Value $Version -NoNewline -Encoding ASCII

# ------------------------------------------------------------------- 3. dependencies
Push-Location $AppDir
try {
    Step 'composer install --no-dev'
    Invoke-Native composer @('install', '--no-dev', '--optimize-autoloader', '--no-interaction', '--prefer-dist', '--no-progress')

    Step 'npm ci + npm run build (assets Vite)'
    Invoke-Native npm @('ci', '--no-audit', '--no-fund')
    Invoke-Native npm @('run', 'build')
    Remove-Item node_modules -Recurse -Force

    Step 'npm ci listener TikTok'
    Push-Location 'services\tiktok-gift-listener'
    try {
        Invoke-Native npm @('ci', '--omit=dev', '--no-audit', '--no-fund')
    } finally { Pop-Location }
} finally { Pop-Location }

# ---------------------------------------------------------------------- 4. PHP portable
Step "PHP portable $PhpBranch (NTS x64)"
$releases = Invoke-RestMethod -Uri 'https://windows.php.net/downloads/releases/releases.json'
$branch = $releases.$PhpBranch
if (-not $branch) { throw "Branch PHP $PhpBranch tidak ada di releases.json" }
$phpBuild = $branch.PSObject.Properties | Where-Object { $_.Name -match '^nts-vs\d+-x64$' } | Select-Object -First 1
if (-not $phpBuild) { throw "Build NTS x64 untuk PHP $PhpBranch tidak ditemukan" }
$zipInfo = $phpBuild.Value.zip
$phpZip = Join-Path $Cache $zipInfo.path
Get-Download "https://windows.php.net/downloads/releases/$($zipInfo.path)" $phpZip $zipInfo.sha256
Expand-Archive -Path $phpZip -DestinationPath $PhpDir -Force
Copy-Item (Join-Path $PSScriptRoot 'php.ini') (Join-Path $PhpDir 'php.ini')
Write-Host "    PHP $($branch.version)"

Step 'CA bundle (HTTPS ke GitHub API dari PHP)'
Invoke-WebRequest -Uri 'https://curl.se/ca/cacert.pem' -OutFile (Join-Path $PhpDir 'cacert.pem') -UseBasicParsing

# --------------------------------------------------------------------- 5. Node portable
Step "Node.js $NodeMajor portable (x64)"
$nodeRelease = (Invoke-RestMethod -Uri 'https://nodejs.org/dist/index.json') |
    Where-Object { $_.version -like "v$NodeMajor.*" } | Select-Object -First 1
$nodeVersion = $nodeRelease.version
$nodeName = "node-$nodeVersion-win-x64"
$shasums = (Invoke-WebRequest -Uri "https://nodejs.org/dist/$nodeVersion/SHASUMS256.txt" -UseBasicParsing).Content
$nodeSha = ($shasums -split "`n" | Where-Object { $_ -match "\s$nodeName\.zip$" }) -replace '\s.*$', ''
$nodeZip = Join-Path $Cache "$nodeName.zip"
Get-Download "https://nodejs.org/dist/$nodeVersion/$nodeName.zip" $nodeZip $nodeSha
$nodeTmp = Join-Path $Cache $nodeName
if (Test-Path $nodeTmp) { Remove-Item $nodeTmp -Recurse -Force }
Expand-Archive -Path $nodeZip -DestinationPath $Cache -Force
# Listener cuma butuh node.exe (node_modules-nya sudah ikut di build), npm tidak perlu.
Copy-Item (Join-Path $nodeTmp 'node.exe') $NodeDir
Remove-Item $nodeTmp -Recurse -Force
Write-Host "    Node $nodeVersion"

# --------------------------------------------------------------------------- 6. launcher
Step 'Launcher'
Copy-Item (Join-Path $PSScriptRoot 'launcher') (Join-Path $Build 'launcher') -Recurse

# ------------------------------------------------------------------------ 7. smoke test
if (-not $SkipSmokeTest) {
    Step 'Smoke test: jalankan launcher sungguhan di folder data sementara'
    $smokeData = Join-Path $Cache 'smoke-appdata'
    if (Test-Path $smokeData) { Remove-Item $smokeData -Recurse -Force }
    New-Item -ItemType Directory -Path $smokeData | Out-Null

    $launcher = Join-Path $Build 'launcher\GiftTokTok.ps1'
    $oldAppData = $env:APPDATA
    $env:APPDATA = $smokeData
    $env:GIFTTOKTOK_HEADLESS = '1'
    try {
        & powershell.exe -NoProfile -ExecutionPolicy Bypass -File $launcher -NoBrowser
        if ($LASTEXITCODE -ne 0) {
            Get-Content (Join-Path $smokeData 'GiftTokTok\run\launcher.log') -ErrorAction SilentlyContinue
            throw "Launcher gagal (exit $LASTEXITCODE)"
        }

        $envText = Get-Content (Join-Path $smokeData 'GiftTokTok\.env') -Raw
        $port = [regex]::Match($envText, 'APP_PORT=(\d+)').Groups[1].Value
        $login = Invoke-WebRequest -Uri "http://127.0.0.1:$port/login" -UseBasicParsing
        if ($login.StatusCode -ne 200) { throw "Halaman login HTTP $($login.StatusCode)" }
        Write-Host "    /login OK (port $port)"

        # Jalankan launcher kedua kali = simulasi buka ulang / sesudah update.
        & powershell.exe -NoProfile -ExecutionPolicy Bypass -File $launcher -Stop
        & powershell.exe -NoProfile -ExecutionPolicy Bypass -File $launcher -NoBrowser
        if ($LASTEXITCODE -ne 0) { throw "Launcher gagal saat dibuka ulang (exit $LASTEXITCODE)" }
        Write-Host '    buka ulang OK'
    } finally {
        & powershell.exe -NoProfile -ExecutionPolicy Bypass -File $launcher -Stop
        $env:APPDATA = $oldAppData
        Remove-Item Env:\GIFTTOKTOK_HEADLESS
        # File yang dibuat app saat jalan, tidak boleh ikut ke installer.
        Get-ChildItem (Join-Path $AppDir 'bootstrap\cache') -Filter '*.php' -ErrorAction SilentlyContinue |
            Where-Object { $_.Name -notin 'packages.php', 'services.php' } | Remove-Item -Force
        Remove-Item $smokeData -Recurse -Force -ErrorAction SilentlyContinue
    }
}

# ------------------------------------------------------------------------ 8. Inno Setup
Step 'Compile installer (Inno Setup)'
$iscc = @(
    (Join-Path ${env:ProgramFiles(x86)} 'Inno Setup 6\ISCC.exe'),
    (Join-Path $env:ProgramFiles 'Inno Setup 6\ISCC.exe')
) | Where-Object { Test-Path $_ } | Select-Object -First 1
if (-not $iscc) {
    Invoke-Native choco @('install', 'innosetup', '-y', '--no-progress')
    $iscc = Join-Path ${env:ProgramFiles(x86)} 'Inno Setup 6\ISCC.exe'
}

$isccArgs = @("/DAppVersion=$Version", "/DBuildDir=$Build")
$isl = Join-Path $Cache 'Indonesian.isl'
try {
    Invoke-WebRequest -Uri 'https://raw.githubusercontent.com/jrsoftware/issrc/main/Files/Languages/Unofficial/Indonesian.isl' -OutFile $isl -UseBasicParsing
    $isccArgs += "/DIndonesianIsl=$isl"
} catch {
    Write-Warning 'Indonesian.isl gagal diunduh - wizard installer pakai bahasa Inggris.'
}

Invoke-Native $iscc ($isccArgs + (Join-Path $PSScriptRoot 'gifttoktok.iss'))

$exe = Join-Path $Build "output\GiftTokTok-Setup-$Version.exe"
Write-Host "`nSelesai: $exe ($([math]::Round((Get-Item $exe).Length / 1MB, 1)) MB)" -ForegroundColor Green
