# Iniciar Servidor Local con PHP 8.2 y extensiones completas
$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$env:PHPRC = "$scriptDir\php82"
$env:Path = "$scriptDir\php82;" + $env:Path

Write-Host "=======================================================" -ForegroundColor Cyan
Write-Host "  Iniciando Servidor de Desarrollo CodeIgniter 4" -ForegroundColor Green
Write-Host "  Ventanilla Digital de Movilidad - Uriangato" -ForegroundColor White
Write-Host "=======================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Servidor listo en: http://localhost:8080" -ForegroundColor Yellow
Write-Host "Presiona Ctrl+C para detener el servidor." -ForegroundColor Gray
Write-Host ""

& "$scriptDir\php82\php.exe" spark serve --php="$scriptDir\php82\php.exe"
