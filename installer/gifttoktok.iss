; Installer GiftTokTok (Inno Setup 6). Dikompilasi oleh installer/build.ps1:
;   ISCC.exe /DAppVersion=1.2.0 /DBuildDir=<folder hasil build> installer\gifttoktok.iss
;
; - Install per-user ke %LOCALAPPDATA%\Programs\GiftTokTok (tanpa hak admin/UAC).
; - Data user (database, upload, .env) ada di %APPDATA%\GiftTokTok - tidak disentuh
;   saat update maupun uninstall.
; - AppId TETAP (jangan diganti!) - itu yang membuat installer versi baru dianggap
;   upgrade dari versi lama, bukan aplikasi kedua.

#ifndef AppVersion
  #define AppVersion "0.0.0"
#endif
#ifndef BuildDir
  #define BuildDir "build"
#endif

#define AppName "GiftTokTok"
#define Launcher "{app}\launcher\GiftTokTok.ps1"
#define PowerShell "{sys}\WindowsPowerShell\v1.0\powershell.exe"
#define PsArgs "-NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File"

[Setup]
AppId={{6B0F3C1E-7A54-4C1B-9E39-2F1D7C8A9B42}
AppName={#AppName}
AppVersion={#AppVersion}
AppVerName={#AppName} {#AppVersion}
AppPublisher={#AppName}
AppPublisherURL=https://github.com/hafizirwandi/gifttoktok
AppUpdatesURL=https://github.com/hafizirwandi/gifttoktok/releases
VersionInfoVersion={#AppVersion}
DefaultDirName={localappdata}\Programs\{#AppName}
DefaultGroupName={#AppName}
DisableProgramGroupPage=yes
DisableDirPage=auto
PrivilegesRequired=lowest
ArchitecturesAllowed=x64compatible
ArchitecturesInstallIn64BitMode=x64compatible
MinVersion=10.0
OutputDir={#BuildDir}\output
OutputBaseFilename=GiftTokTok-Setup-{#AppVersion}
SetupIconFile=launcher\gifttoktok.ico
UninstallDisplayIcon={app}\launcher\gifttoktok.ico
UninstallDisplayName={#AppName}
Compression=lzma2/max
SolidCompression=yes
WizardStyle=modern
CloseApplications=no
RestartApplications=no

[Languages]
; Indonesian.isl bukan bawaan Inno Setup (terjemahan "unofficial") - build.ps1
; mengunduhnya & mengoper /DIndonesianIsl=<path>. Kalau gagal diunduh, wizard English.
#ifdef IndonesianIsl
Name: "indonesian"; MessagesFile: "{#IndonesianIsl}"
#endif
Name: "english"; MessagesFile: "compiler:Default.isl"

[Tasks]
Name: "desktopicon"; Description: "{cm:CreateDesktopIcon}"; GroupDescription: "{cm:AdditionalIcons}"

[InstallDelete]
; Bersihkan versi lama total sebelum file baru disalin - file yang DIHAPUS di versi baru
; (mis. class PHP/migration lama) tidak boleh tertinggal. Aman: data user ada di
; %APPDATA%, dan junction public\storage sudah dilepas lebih dulu di PrepareToInstall.
Type: filesandordirs; Name: "{app}\app"
Type: filesandordirs; Name: "{app}\runtime"

[Files]
Source: "{#BuildDir}\app\*"; DestDir: "{app}\app"; Flags: recursesubdirs createallsubdirs ignoreversion
Source: "{#BuildDir}\runtime\*"; DestDir: "{app}\runtime"; Flags: recursesubdirs createallsubdirs ignoreversion
Source: "{#BuildDir}\launcher\*"; DestDir: "{app}\launcher"; Flags: recursesubdirs createallsubdirs ignoreversion

[Icons]
Name: "{group}\{#AppName}"; Filename: "{#PowerShell}"; Parameters: "{#PsArgs} ""{#Launcher}"""; WorkingDir: "{app}"; IconFilename: "{app}\launcher\gifttoktok.ico"
Name: "{group}\{#AppName} Listener"; Filename: "{#PowerShell}"; Parameters: "{#PsArgs} ""{#Launcher}"" -Listener"; WorkingDir: "{app}"; IconFilename: "{app}\launcher\gifttoktok.ico"
Name: "{group}\Hentikan {#AppName}"; Filename: "{#PowerShell}"; Parameters: "{#PsArgs} ""{#Launcher}"" -Stop"; WorkingDir: "{app}"; IconFilename: "{app}\launcher\gifttoktok.ico"
Name: "{group}\Folder Data {#AppName}"; Filename: "{userappdata}\{#AppName}"
Name: "{group}\{cm:UninstallProgram,{#AppName}}"; Filename: "{uninstallexe}"
Name: "{autodesktop}\{#AppName}"; Filename: "{#PowerShell}"; Parameters: "{#PsArgs} ""{#Launcher}"""; WorkingDir: "{app}"; IconFilename: "{app}\launcher\gifttoktok.ico"; Tasks: desktopicon
Name: "{autodesktop}\{#AppName} Listener"; Filename: "{#PowerShell}"; Parameters: "{#PsArgs} ""{#Launcher}"" -Listener"; WorkingDir: "{app}"; IconFilename: "{app}\launcher\gifttoktok.ico"; Tasks: desktopicon

[Run]
; Tanpa skipifsilent: update otomatis (/VERYSILENT dari tombol "Update sekarang")
; juga membuka GiftTokTok lagi setelah selesai.
Filename: "{#PowerShell}"; Parameters: "{#PsArgs} ""{#Launcher}"""; Description: "{cm:LaunchProgram,{#AppName}}"; Flags: postinstall nowait runhidden

[UninstallDelete]
; File hasil runtime di folder aplikasi (cache bootstrap, dll). Data user di %APPDATA%
; sengaja dibiarkan - hapus manual folder itu kalau memang ingin bersih total.
Type: filesandordirs; Name: "{app}\app"
Type: filesandordirs; Name: "{app}\runtime"

[Code]
procedure StopGiftTokTok();
var
  ResultCode: Integer;
  Launcher, StorageLink: String;
begin
  Launcher := ExpandConstant('{app}\launcher\GiftTokTok.ps1');
  if FileExists(Launcher) then
    Exec(ExpandConstant('{#PowerShell}'),
      '-NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File "' + Launcher + '" -Stop',
      '', SW_HIDE, ewWaitUntilTerminated, ResultCode);

  { Lepas junction public\storage -> %APPDATA%\GiftTokTok\storage\app\public secara
    eksplisit (rmdir tanpa /s hanya menghapus link-nya) sebelum folder app dihapus,
    supaya file upload user tidak ikut terhapus walau launcher -Stop gagal. }
  StorageLink := ExpandConstant('{app}\app\public\storage');
  if DirExists(StorageLink) then
    Exec(ExpandConstant('{cmd}'), '/c rmdir "' + StorageLink + '"', '', SW_HIDE, ewWaitUntilTerminated, ResultCode);
end;

function PrepareToInstall(var NeedsRestart: Boolean): String;
begin
  StopGiftTokTok();
  Result := '';
end;

procedure CurUninstallStepChanged(CurUninstallStep: TUninstallStep);
begin
  if CurUninstallStep = usUninstall then
    StopGiftTokTok();
end;
