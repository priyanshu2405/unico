Write-Host "Clearing cached views..."
php artisan view:clear

Write-Host "Building frontend assets..."
npm run build

$tempDir = ".\temp_taxpp_deploy"
if (Test-Path $tempDir) { Remove-Item -Recurse -Force $tempDir }
New-Item -ItemType Directory -Path $tempDir | Out-Null

Write-Host "Copying project files (excluding .git, node_modules, tests, zips, etc)..."
# robocopy returns 1 for successful copy
robocopy . $tempDir /E /XD .git node_modules tests scratch temp_taxpp_deploy temp_bluehost_deploy /XF *.zip *.ps1

Write-Host "Zipping the project using tar (fast)..."
if (Test-Path "taxpp.zip") { Remove-Item -Force "taxpp.zip" }
Push-Location $tempDir
tar -a -c -f ..\taxpp.zip *
Pop-Location

Write-Host "Cleaning up temp folder..."
Remove-Item -Recurse -Force $tempDir

Write-Host "Done! Fresh zip created as 'taxpp.zip'."
