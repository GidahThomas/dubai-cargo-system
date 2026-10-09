# Builds the upload package for cPanel hosting.
#
#   powershell -ExecutionPolicy Bypass -File tools\package_for_hosting.ps1
#
# Produces deploy\dubai-tech-plaza-<date>.zip containing:
#   - the application, exactly as committed in git (no .env, backups, logs or local tools)
#   - public\uploads (product photos, logos) as they are on this computer
#   - database.sql: a full copy of the database to import in cPanel > phpMyAdmin
#   - .env.production.example and docs\DEPLOY_CPANEL.md
# The zip holds customer data and password hashes: keep it private and delete it after upload.

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

$dirty = git status --porcelain --untracked-files=no
if ($dirty) {
    throw "There are uncommitted changes. Commit them first so the package matches a known version:`n$dirty"
}

$stamp = Get-Date -Format 'yyyy-MM-dd_HHmm'
$commit = (git rev-parse --short HEAD).Trim()
$deployDir = Join-Path $root 'deploy'
$staging = Join-Path $deployDir "staging-$stamp"
$zipPath = Join-Path $deployDir "dubai-tech-plaza-$stamp.zip"
New-Item -ItemType Directory -Force -Path $staging | Out-Null

Write-Output "1/4 Exporting application (commit $commit)..."
$tarPath = Join-Path $deployDir "app-$stamp.tar"
git archive --format=tar -o $tarPath HEAD
tar -xf $tarPath -C $staging
Remove-Item $tarPath

Write-Output "2/4 Adding uploaded photos and logos..."
Copy-Item -Path (Join-Path $root 'public\uploads\*') -Destination (Join-Path $staging 'public\uploads') -Recurse -Force

Write-Output "3/4 Exporting the database..."
$envFile = Join-Path $root '.env'
$settings = @{}
if (Test-Path $envFile) {
    Get-Content $envFile | Where-Object { $_ -match '^\s*([A-Z_]+)\s*=\s*(.*)$' } | ForEach-Object {
        $null = $_ -match '^\s*([A-Z_]+)\s*=\s*(.*)$'
        $settings[$Matches[1]] = $Matches[2].Trim('"', "'", ' ')
    }
}
$dbName = if ($settings['DB_NAME']) { $settings['DB_NAME'] } else { 'dubai_computer_fast_cargo' }
$dbUser = if ($settings['DB_USER']) { $settings['DB_USER'] } else { 'root' }
$mysqldump = 'C:\xampp\mysql\bin\mysqldump.exe'
$dumpArgs = @('--single-transaction', '--routines', '--default-character-set=utf8mb4', '--skip-comments', "--user=$dbUser")
if ($settings['DB_PASS']) { $dumpArgs += "--password=$($settings['DB_PASS'])" }
$dumpArgs += $dbName
$sql = & $mysqldump @dumpArgs
if ($LASTEXITCODE -ne 0) { throw 'mysqldump failed' }
# Import into whatever database cPanel created: no CREATE DATABASE / USE lines.
$sql | Where-Object { $_ -notmatch '^(CREATE DATABASE|USE )' } | Set-Content -Path (Join-Path $staging 'database.sql') -Encoding utf8

Write-Output "4/4 Compressing..."
Set-Content -Path (Join-Path $staging 'PACKAGE_INFO.txt') -Encoding utf8 -Value @"
Dubai Tech Plaza hosting package
Built: $(Get-Date -Format 'yyyy-MM-dd HH:mm')
Commit: $commit
Follow docs/DEPLOY_CPANEL.md step by step.
This package contains customer data and password hashes. Keep it private; delete it after uploading.
"@
Compress-Archive -Path (Join-Path $staging '*') -DestinationPath $zipPath -Force
Remove-Item -Recurse -Force $staging

$sizeMb = [math]::Round((Get-Item $zipPath).Length / 1MB, 1)
Write-Output "Done: $zipPath ($sizeMb MB)"
