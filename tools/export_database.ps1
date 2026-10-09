# Exports the local database to deploy\database-cloud.sql for a cloud MySQL database (TiDB Cloud,
# PlanetScale, Aiven...): no CREATE DATABASE / USE lines and no table locks, which those services refuse.
# Import it with:  C:\xampp\php\php.exe tools\import_sql.php deploy\database-cloud.sql --env=.env.tidb
#
#   powershell -ExecutionPolicy Bypass -File tools\export_database.ps1
#
# The file contains customer data and password hashes: keep it private and delete it after importing.

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot

$settings = @{}
$envFile = Join-Path $root '.env'
if (Test-Path $envFile) {
    Get-Content $envFile | Where-Object { $_ -match '^\s*([A-Z_]+)\s*=\s*(.*)$' } | ForEach-Object {
        $null = $_ -match '^\s*([A-Z_]+)\s*=\s*(.*)$'
        $settings[$Matches[1]] = $Matches[2].Trim('"', "'", ' ')
    }
}
$dbName = if ($settings['DB_NAME']) { $settings['DB_NAME'] } else { 'dubai_computer_fast_cargo' }
$dbUser = if ($settings['DB_USER']) { $settings['DB_USER'] } else { 'root' }

$dumpArgs = @('--single-transaction', '--skip-lock-tables', '--skip-add-locks', '--skip-comments', '--skip-triggers',
    '--default-character-set=utf8mb4', "--user=$dbUser")
if ($settings['DB_PASS']) { $dumpArgs += "--password=$($settings['DB_PASS'])" }
$dumpArgs += $dbName

$sql = & 'C:\xampp\mysql\bin\mysqldump.exe' @dumpArgs
if ($LASTEXITCODE -ne 0) { throw 'mysqldump failed' }

$outDir = Join-Path $root 'deploy'
New-Item -ItemType Directory -Force $outDir | Out-Null
$outFile = Join-Path $outDir 'database-cloud.sql'

# UTF-8 without BOM (Windows PowerShell's utf8 adds one, which some servers reject).
$lines = $sql | Where-Object { $_ -notmatch '^(CREATE DATABASE|USE )' }
[System.IO.File]::WriteAllLines($outFile, [string[]] $lines, (New-Object System.Text.UTF8Encoding $false))

Write-Output "Saved $outFile ($([math]::Round((Get-Item $outFile).Length / 1KB)) KB)"
