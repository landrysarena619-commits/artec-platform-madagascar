param(
    [string]$Database = $(if ($env:DB_NAME) { $env:DB_NAME } else { 'artec_platform' }),
    [int]$RetentionDays = 30
)

if (-not $env:MYSQLDUMP_EXE -or -not $env:MYSQL_CNF) {
    throw 'Set MYSQLDUMP_EXE and MYSQL_CNF. Store credentials in a protected MySQL option file.'
}

$backupDirectory = Join-Path $PSScriptRoot 'backups'
New-Item -ItemType Directory -Path $backupDirectory -Force | Out-Null
$timestamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$backupFile = Join-Path $backupDirectory "$Database-$timestamp.sql"

& $env:MYSQLDUMP_EXE "--defaults-extra-file=$env:MYSQL_CNF" '--single-transaction' '--routines' '--triggers' "--result-file=$backupFile" $Database
if ($LASTEXITCODE -ne 0) {
    Remove-Item -LiteralPath $backupFile -ErrorAction SilentlyContinue
    throw "mysqldump failed with exit code $LASTEXITCODE"
}

Get-ChildItem -Path $backupDirectory -Filter '*.sql' -File |
    Where-Object { $_.LastWriteTime -lt (Get-Date).AddDays(-$RetentionDays) } |
    Remove-Item -Force

Write-Output "Database backup created: $backupFile"