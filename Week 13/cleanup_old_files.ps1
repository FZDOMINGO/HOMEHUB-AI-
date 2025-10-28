# HomeHub Cleanup Script
# Removes test, debug, and old configuration files after migration to env.php system
# Review this file before running!

Write-Host "HomeHub Cleanup Script" -ForegroundColor Cyan
Write-Host "======================" -ForegroundColor Cyan
Write-Host ""
Write-Host "This script will remove test/debug files and old config files." -ForegroundColor Yellow
Write-Host "A backup folder will be created before deletion." -ForegroundColor Yellow
Write-Host ""

$homehubPath = "c:\xampp\htdocs\HomeHub"
$backupPath = "$homehubPath\backup_old_files_$(Get-Date -Format 'yyyyMMdd_HHmmss')"

# Ask for confirmation
$confirmation = Read-Host "Do you want to proceed? (yes/no)"
if ($confirmation -ne "yes") {
    Write-Host "Cleanup cancelled." -ForegroundColor Red
    exit
}

# Create backup folder
Write-Host ""
Write-Host "Creating backup folder: $backupPath" -ForegroundColor Green
New-Item -ItemType Directory -Path $backupPath -Force | Out-Null

# Backup and remove test files
Write-Host "Backing up and removing test_*.php files..." -ForegroundColor Yellow
Get-ChildItem -Path $homehubPath -Filter "test_*.php" | ForEach-Object {
    Copy-Item $_.FullName -Destination $backupPath
    Remove-Item $_.FullName
    Write-Host "  Removed: $($_.Name)" -ForegroundColor Gray
}

# Backup and remove check files
Write-Host "Backing up and removing check_*.php files..." -ForegroundColor Yellow
Get-ChildItem -Path $homehubPath -Filter "check_*.php" | ForEach-Object {
    Copy-Item $_.FullName -Destination $backupPath
    Remove-Item $_.FullName
    Write-Host "  Removed: $($_.Name)" -ForegroundColor Gray
}

# Backup and remove debug files
Write-Host "Backing up and removing debug*.php files (root only)..." -ForegroundColor Yellow
Get-ChildItem -Path $homehubPath -Filter "debug*.php" | ForEach-Object {
    Copy-Item $_.FullName -Destination $backupPath
    Remove-Item $_.FullName
    Write-Host "  Removed: $($_.Name)" -ForegroundColor Gray
}

# Backup and remove prepare files
Write-Host "Backing up and removing prepare_*.php files..." -ForegroundColor Yellow
Get-ChildItem -Path $homehubPath -Filter "prepare_*.php" | ForEach-Object {
    Copy-Item $_.FullName -Destination $backupPath
    Remove-Item $_.FullName
    Write-Host "  Removed: $($_.Name)" -ForegroundColor Gray
}

# Backup and remove backup files
Write-Host "Backing up and removing *_backup.php files..." -ForegroundColor Yellow
Get-ChildItem -Path $homehubPath -Recurse -Filter "*_backup.php" | ForEach-Object {
    $relativePath = $_.FullName.Replace($homehubPath, "").TrimStart("\")
    $backupSubPath = Join-Path $backupPath (Split-Path $relativePath -Parent)
    if (!(Test-Path $backupSubPath)) {
        New-Item -ItemType Directory -Path $backupSubPath -Force | Out-Null
    }
    Copy-Item $_.FullName -Destination (Join-Path $backupSubPath (Split-Path $relativePath -Leaf))
    Remove-Item $_.FullName
    Write-Host "  Removed: $relativePath" -ForegroundColor Gray
}

# Backup and remove old config files (keeping env.php, database.php, db_connect.php)
Write-Host "Backing up and removing old config files..." -ForegroundColor Yellow
$oldConfigFiles = @(
    "$homehubPath\config\db_connect.PRODUCTION.php",
    "$homehubPath\config\db_connect_HOSTINGER.php",
    "$homehubPath\config\db_connect.HOSTINGER_TEMPLATE.php"
)

foreach ($file in $oldConfigFiles) {
    if (Test-Path $file) {
        $fileName = Split-Path $file -Leaf
        Copy-Item $file -Destination (Join-Path $backupPath $fileName)
        Remove-Item $file
        Write-Host "  Removed: config\$fileName" -ForegroundColor Gray
    }
}

# Remove old HTML test files
Write-Host "Backing up and removing test HTML files..." -ForegroundColor Yellow
Get-ChildItem -Path $homehubPath -Filter "test_*.html" | ForEach-Object {
    Copy-Item $_.FullName -Destination $backupPath
    Remove-Item $_.FullName
    Write-Host "  Removed: $($_.Name)" -ForegroundColor Gray
}

Get-ChildItem -Path $homehubPath -Filter "debug_*.html" | ForEach-Object {
    Copy-Item $_.FullName -Destination $backupPath
    Remove-Item $_.FullName
    Write-Host "  Removed: $($_.Name)" -ForegroundColor Gray
}

# List remaining debug files that should be kept
Write-Host ""
Write-Host "Debug files kept (these are updated and production-ready):" -ForegroundColor Cyan
Write-Host "  - tenant\debug_navbar.php (updated with env.php)" -ForegroundColor Green
Write-Host "  - landlord\debug_navbar.php (updated with env.php)" -ForegroundColor Green

# Summary
Write-Host ""
Write-Host "Cleanup Complete!" -ForegroundColor Green
Write-Host "Backup location: $backupPath" -ForegroundColor Cyan
Write-Host ""
Write-Host "If you need to restore any files, they are in the backup folder." -ForegroundColor Yellow
Write-Host "After verifying everything works, you can delete the backup folder." -ForegroundColor Yellow
