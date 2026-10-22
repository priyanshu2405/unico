Write-Host "Clearing cache files..."
if (Test-Path "bootstrap\cache\config.php") { Remove-Item -Force "bootstrap\cache\config.php" }
if (Test-Path "bootstrap\cache\routes-v7.php") { Remove-Item -Force "bootstrap\cache\routes-v7.php" }
if (Test-Path "bootstrap\cache\services.php") { Remove-Item -Force "bootstrap\cache\services.php" }
if (Test-Path "bootstrap\cache\packages.php") { Remove-Item -Force "bootstrap\cache\packages.php" }
Get-ChildItem -Path "storage\framework\views\*" -Exclude .gitignore -ErrorAction SilentlyContinue | Remove-Item -Force -ErrorAction SilentlyContinue

Write-Host "Building frontend assets..."
npm run build

$tempDir = ".\temp_bluehost_deploy"
if (Test-Path $tempDir) { Remove-Item -Recurse -Force $tempDir }
New-Item -ItemType Directory -Path $tempDir | Out-Null

Write-Host "Copying project files..."
# Robocopy directory mirror excluding unwanted dev/temp folders
robocopy . $tempDir /E /XD .git node_modules tests scratch temp_bluehost_deploy .zencoder /XF *.zip *.ps1 *.log check_db.php scratch_roles.php debug_query.php fetch_user.php get_admins.php get_user.php query_data.php schema_check.php scratch_backfill_taxes.php status_update.php database_export.sql

if (Test-Path "$tempDir\.env.production") {
    Copy-Item "$tempDir\.env.production" "$tempDir\.env" -Force
}

if (Test-Path "database\bluehost_database_dump.sql") {
    Copy-Item "database\bluehost_database_dump.sql" "$tempDir\database\bluehost_database_dump.sql" -Force
}

if (Test-Path "$tempDir\public\hot") {
    Remove-Item -Force "$tempDir\public\hot"
}

# Ensure storage subdirectories exist and have .gitignore
$storageDirs = @(
    "$tempDir\storage\app",
    "$tempDir\storage\app\public",
    "$tempDir\storage\framework",
    "$tempDir\storage\framework\cache",
    "$tempDir\storage\framework\cache\data",
    "$tempDir\storage\framework\sessions",
    "$tempDir\storage\framework\views",
    "$tempDir\storage\logs"
)
foreach ($dir in $storageDirs) {
    if (-not (Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force | Out-Null
    }
    if (-not (Test-Path "$dir\.gitignore")) {
        Set-Content -Path "$dir\.gitignore" -Value "*`n!.gitignore"
    }
}

$zipName = "unicou_production_bluehost.zip"
Write-Host "Creating production zip '$zipName'..."
if (Test-Path $zipName) { Remove-Item -Force $zipName }

Add-Type -AssemblyName "System.IO.Compression.FileSystem"
$sourcePath = (Resolve-Path $tempDir).Path
$destPath = (Join-Path (Get-Location) $zipName)
[System.IO.Compression.ZipFile]::CreateFromDirectory($sourcePath, $destPath, [System.IO.Compression.CompressionLevel]::Fastest, $false)


Write-Host "Cleaning up temp folder..."
Remove-Item -Recurse -Force $tempDir

$todayZip = "..\$((Get-Date).ToString('dd-MM-yy')).zip"
Copy-Item $zipName "unicou_production.zip" -Force
Copy-Item $zipName "..\$zipName" -Force
Copy-Item $zipName $todayZip -Force
Copy-Item $zipName "..\unicou_production.zip" -Force

$zipItem = Get-Item $zipName
$sizeMB = [math]::Round($zipItem.Length / 1MB, 2)
Write-Host "SUCCESS! Production archive ready: $zipName ($sizeMB MB)"
Write-Host "Copies created:"
Write-Host " - .\$zipName"
Write-Host " - .\unicou_production.zip"
Write-Host " - ..\$zipName"
Write-Host " - $todayZip"
Write-Host " - ..\unicou_production.zip"

