# Registers the system's background jobs in Windows Task Scheduler (run once; re-running updates them).
#
#   powershell -ExecutionPolicy Bypass -File tools\schedule_tasks.ps1
#
#   DubaiCargo\SendMessages   every minute   sends queued WhatsApp/SMS messages
#   DubaiCargo\DailyBackup    02:00 daily    database + uploads backup (tools\backup.php)
#
# php-win.exe runs without opening a console window. Tasks run as the current user while logged on.
# To remove:  schtasks /Delete /TN "DubaiCargo\SendMessages" /F   (and DailyBackup)

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$php = 'C:\xampp\php\php-win.exe'
if (-not (Test-Path $php)) { $php = 'C:\xampp\php\php.exe' }

$jobs = @(
    @{ Name = 'DubaiCargo\SendMessages'; Script = 'tools\send_messages.php'; Schedule = @('/SC', 'MINUTE', '/MO', '1') },
    @{ Name = 'DubaiCargo\DailyBackup'; Script = 'tools\backup.php'; Schedule = @('/SC', 'DAILY', '/ST', '02:00') }
)

foreach ($job in $jobs) {
    $command = '"' + $php + '" "' + (Join-Path $root $job.Script) + '"'
    $arguments = @('/Create', '/F', '/TN', $job.Name, '/TR', $command) + $job.Schedule
    & schtasks.exe @arguments | Out-Null
    if ($LASTEXITCODE -ne 0) { throw "Could not register $($job.Name)" }
    Write-Output "Registered $($job.Name)"
}
