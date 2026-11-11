$ErrorActionPreference = 'Stop'
$config = Join-Path (Split-Path $PSScriptRoot -Parent) '.demo\httpd-demo.conf'
$processes = @(Get-CimInstance Win32_Process -Filter "name='httpd.exe'" | Where-Object { $_.CommandLine -like "*$config*" })
if (!$processes.Count) { Write-Host 'This demo is already stopped.'; exit 0 }
# This Apache runs as a console process, not an installed Windows service.
# Match its exact -f path, then stop its child workers and parent only.
$all = @(Get-CimInstance Win32_Process -Filter "name='httpd.exe'")
$ids = @($processes | ForEach-Object { $_.ProcessId })
$workers = @($all | Where-Object { $_.ParentProcessId -in $ids })
foreach ($p in @($workers) + @($processes)) {
    Stop-Process -Id $p.ProcessId -Force -ErrorAction SilentlyContinue
}
Write-Host 'Demo Apache stopped. MySQL is left running for other projects; stop it from XAMPP Control Panel when no longer needed.'
