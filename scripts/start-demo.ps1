[CmdletBinding()]
param(
    [string]$XamppPath = 'C:\xampp',
    [ValidateRange(1024,65535)][int]$Port = 8080,
    [string]$DbName = 'vali_store',
    [string]$DbUser = 'root',
    [string]$DbPassword = '',
    [ValidateRange(1,65535)][int]$DbPort = 3306,
    [switch]$NoBrowser,
    [switch]$CheckOnly
)
$ErrorActionPreference = 'Stop'
$project = Split-Path $PSScriptRoot -Parent
$state = Join-Path $project '.demo'
$php = Join-Path $XamppPath 'php\php.exe'
$apache = Join-Path $XamppPath 'apache\bin\httpd.exe'
$mysql = Join-Path $XamppPath 'mysql\bin\mysqld.exe'
function Test-Port([int]$Number) {
    $client = New-Object Net.Sockets.TcpClient
    try { $client.Connect('127.0.0.1', $Number); return $true } catch { return $false } finally { $client.Dispose() }
}
function Invoke-Php([string]$File) {
    $check = Start-Process $php -ArgumentList @("`"$File`"") -Wait -NoNewWindow -PassThru
    if ($check.ExitCode -ne 0) { throw "PHP command failed: $File" }
}
function Apache-Value([string]$Value) {
    if ($Value -match '[\r\n\x00]') { throw 'Configuration values cannot contain control characters.' }
    return $Value.Replace('\','/').Replace('"','\"')
}
try {
    if ($env:OS -ne 'Windows_NT') { throw 'Run this script in Windows PowerShell, outside WSL.' }
    if ($DbName -notmatch '^[a-zA-Z0-9_]+$') { throw 'Invalid database name.' }
    if ($XamppPath -match '\s') { throw 'Choose an XAMPP path without spaces, for example C:\xampp.' }
    if (!(Test-Path $php)) {
        if ($CheckOnly) { throw "XAMPP not installed at $XamppPath" }
        $identity = [Security.Principal.WindowsIdentity]::GetCurrent()
        $admin = (New-Object Security.Principal.WindowsPrincipal($identity)).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
        if (!$admin) {
            # Forward every value as a quoted PowerShell literal. UAC is the
            # only interaction required for installing software on Windows.
            $forward = @('-XamppPath', $XamppPath, '-DbName', $DbName, '-DbUser', $DbUser, '-DbPassword', $DbPassword, '-DbPort', "$DbPort")
            if ($PSBoundParameters.ContainsKey('Port')) { $forward += @('-Port', "$Port") }
            $parts = @()
            for ($i=0; $i -lt $forward.Count; $i+=2) {
                $parts += $forward[$i]
                $parts += ("'" + $forward[$i+1].Replace("'", "''") + "'")
            }
            if ($NoBrowser) { $parts += '-NoBrowser' }
            $scriptLiteral = "'" + $PSCommandPath.Replace("'", "''") + "'"
            $command = '$global:LASTEXITCODE=0; & ' + $scriptLiteral + ' ' + ($parts -join ' ') + '; exit $LASTEXITCODE'
            $encoded = [Convert]::ToBase64String([Text.Encoding]::Unicode.GetBytes($command))
            Write-Host 'Windows will request permission to install XAMPP. Choose Yes to continue.'
            $elevated = Start-Process powershell.exe -Verb RunAs -ArgumentList @('-NoProfile', '-ExecutionPolicy', 'Bypass', '-EncodedCommand', $encoded) -Wait -PassThru
            exit $elevated.ExitCode
        }
        if ((Test-Path $XamppPath) -and @(Get-ChildItem $XamppPath -Force).Count) { throw 'XAMPP target is not empty. Use another -XamppPath.' }
        New-Item -ItemType Directory -Force $state | Out-Null
        $installer = Join-Path $state 'xampp-installer.exe'
        $url = 'https://sourceforge.net/projects/xampp/files/XAMPP%20Windows/8.2.12/xampp-windows-x64-8.2.12-0-VS16-installer.exe/download'
        $hash = '12E818CE5AEC79FE646606DF3A80B35DA865EC0213646AD7C92044DCFCEC7535'
        [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
        $vcRuntime = Get-ItemProperty 'HKLM:\SOFTWARE\Microsoft\VisualStudio\14.0\VC\Runtimes\x64' -ErrorAction SilentlyContinue
        if (!$vcRuntime -or $vcRuntime.Installed -ne 1) {
            Write-Host 'Installing Microsoft Visual C++ Runtime x64...'
            $vcInstaller = Join-Path $state 'vc_redist.x64.exe'
            Invoke-WebRequest -UseBasicParsing -Uri 'https://aka.ms/vs/17/release/vc_redist.x64.exe' -OutFile $vcInstaller
            $signature = Get-AuthenticodeSignature $vcInstaller
            if ($signature.Status -ne 'Valid' -or $signature.SignerCertificate.Subject -notmatch 'O=Microsoft Corporation') { throw 'Visual C++ installer signature is invalid.' }
            $vc = Start-Process $vcInstaller -ArgumentList @('/install','/quiet','/norestart') -Wait -PassThru
            if ($vc.ExitCode -eq 3010) { throw 'Visual C++ Runtime installed. Restart Windows, then run demo.cmd again.' }
            if ($vc.ExitCode -notin @(0,1638)) { throw "Visual C++ installation failed: $($vc.ExitCode)" }
        }
        Write-Host 'Downloading XAMPP 8.2.12 (about 158 MB)...'
        if (!(Test-Path $installer) -or (Get-FileHash $installer -Algorithm SHA256).Hash -ne $hash) {
            Invoke-WebRequest -UseBasicParsing -Uri $url -OutFile $installer
        }
        if ((Get-FileHash $installer -Algorithm SHA256).Hash -ne $hash) { throw 'Installer SHA256 mismatch. Installation cancelled.' }
        $process = Start-Process $installer -ArgumentList @('--mode','unattended','--unattendedmodeui','none','--prefix', $XamppPath) -Wait -PassThru
        if ($process.ExitCode -ne 0) { throw "XAMPP installer failed: $($process.ExitCode)" }
    }
    $mysqlAdmin = Join-Path $XamppPath 'mysql\bin\mysqladmin.exe'
    foreach ($exe in @($php,$apache,$mysql,$mysqlAdmin)) { if (!(Test-Path $exe)) { throw "Missing executable: $exe" } }
    $runtimeFile = Join-Path $PSScriptRoot 'bootstrap-demo.php'
    $check = Start-Process $php -ArgumentList @('"' + $runtimeFile + '"', '--check-runtime') -Wait -NoNewWindow -PassThru
    if ($check.ExitCode -ne 0) { throw 'PHP extension check failed. Enable mysqli, mbstring and fileinfo in php/php.ini.' }
    if ($CheckOnly) { Write-Host 'XAMPP runtime check passed; no services started or database modified.'; exit 0 }
    New-Item -ItemType Directory -Force $state | Out-Null
    $env:VALI_DB_HOST='127.0.0.1'; $env:VALI_DB_NAME=$DbName
    $env:VALI_DB_USER=$DbUser; $env:VALI_DB_PASSWORD=$DbPassword; $env:VALI_DB_PORT="$DbPort"
    if (!(Test-Port $DbPort)) {
        # The installed XAMPP configuration owns the database port. Do not
        # override an existing server's port or rewrite its data directory.
        $process = Start-Process $mysql -ArgumentList @("--defaults-file=$XamppPath\mysql\bin\my.ini",'--standalone') -WorkingDirectory "$XamppPath\mysql\bin" -PassThru
        $process.Id | Set-Content (Join-Path $state 'mysql.pid')
        $deadline = (Get-Date).AddSeconds(45)
        do {
            if ($process.HasExited -or (Get-Date) -gt $deadline) { throw "MySQL did not start on port $DbPort. Check $XamppPath\mysql\data\mysql_error.log and my.ini." }
            Start-Sleep -Milliseconds 500
            # An open TCP socket can precede the MariaDB protocol greeting.
            $ping = Start-Process $mysqlAdmin -ArgumentList @(
                '--host=127.0.0.1', "--port=$DbPort", '--connect-timeout=2', '--silent', 'ping'
            ) -NoNewWindow -Wait -PassThru `
              -RedirectStandardOutput (Join-Path $state 'mysql-ping.out') `
              -RedirectStandardError (Join-Path $state 'mysql-ping.err')
        } until ($ping.ExitCode -eq 0)
    }
    Write-Host 'Preparing database (existing data is preserved)...'
    Invoke-Php (Join-Path $PSScriptRoot 'bootstrap-demo.php')
    Invoke-Php (Join-Path $PSScriptRoot 'verify-demo.php')
    if (!$PSBoundParameters.ContainsKey('Port')) {
        $saved = Join-Path $state 'httpd-demo.conf'
        $own = Get-CimInstance Win32_Process -Filter "name='httpd.exe'" | Where-Object { $_.CommandLine -like "*$saved*" }
        if ($own -and (Test-Path $saved) -and (Get-Content $saved -Raw) -match '(?m)^Listen 127\.0\.0\.1:(\d+)\s*$') {
            $Port = [int]$Matches[1]
        } else {
            for ($candidate=8080; $candidate -le 8099; $candidate++) {
                if (!(Test-Port $candidate)) { $Port=$candidate; break }
                if ($candidate -eq 8099) { throw 'No demo port available in 8080..8099. Specify -Port explicitly.' }
            }
        }
    }
    $url = "http://127.0.0.1:$Port/vonam/"
    $configFile = Join-Path $state 'httpd-demo.conf'
    # Bind the isolated Apache instance to loopback; leave port 80/443 and
    # the installed Apache configuration alone.
    $config = Get-Content "$XamppPath\apache\conf\httpd.conf" -Raw
    $config = $config -replace '(?m)^\s*Listen\s+[^\r\n]+', "Listen 127.0.0.1:$Port"
    $config = $config -replace '(?m)^\s*ServerName\s+[^\r\n]+', "ServerName 127.0.0.1:$Port"
    $config = $config -replace '(?m)^(\s*Include\s+[^\r\n]*httpd-ssl.conf[^\r\n]*)', '# $1'
    $root = Apache-Value (Join-Path $project 'public')
    $config += "`r`nPidFile `"$(Apache-Value (Join-Path $state 'apache.pid'))`"`r`n"
    $config += "Alias /vonam `"$root`"`r`n<Directory `"$root`">`r`nAllowOverride All`r`nRequire local`r`n</Directory>`r`n"
    foreach ($key in @('HOST','NAME','USER','PASSWORD','PORT')) {
        $value = [Environment]::GetEnvironmentVariable("VALI_DB_$key")
        $config += "SetEnv VALI_DB_$key `"$(Apache-Value $value)`"`r`n"
    }
    $config += "<DirectoryMatch `"/(?:\.git|\.demo|backups|ban-giao|baocao|artifacts|tools)(?:/|$)`">`r`nRequire all denied`r`n</DirectoryMatch>`r`n"
    if (Test-Port $Port) {
        # Reuse only the Apache instance created for this checkout.
        $running = Get-CimInstance Win32_Process -Filter "name='httpd.exe'" | Where-Object { $_.CommandLine -like "*$configFile*" }
        if (!$running -or !(Test-Path $configFile) -or (Get-Content $configFile -Raw) -ne $config) { throw "Port $Port is occupied or configuration changed. Stop this demo first, or use -Port 8081." }
    } else {
        [IO.File]::WriteAllText($configFile, $config, (New-Object Text.UTF8Encoding($false)))
        $check = Start-Process $apache -ArgumentList @("-t", "-f", "`"$configFile`"") -Wait -NoNewWindow -PassThru
        if ($check.ExitCode -ne 0) { throw 'Apache configuration check failed.' }
        Start-Process $apache -ArgumentList @('-f', "`"$configFile`"") -WorkingDirectory "$XamppPath\apache\bin" | Out-Null
    }
    $ready = $false
    for ($i=0; $i -lt 40; $i++) {
        try {
            $response = Invoke-WebRequest -UseBasicParsing -Uri $url -TimeoutSec 3
            if ($response.StatusCode -eq 200 -and $response.Content -match 'ValiStore' -and $response.Content -notmatch 'Fatal error|Warning:|Notice:') { $ready = $true; break }
        } catch { }
        Start-Sleep -Milliseconds 500
    }
    if (!$ready) { throw 'Demo HTTP check failed. Check XAMPP apache/logs/error.log.' }
    Write-Host "`nDemo ready: $url`nAdmin: admin / admin123`nCustomer: user1 / user123`nExisting accounts retain their current passwords."
    if (!$NoBrowser) { Start-Process $url }
} catch { Write-Error $_; exit 1 }
