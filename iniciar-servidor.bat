@echo off
title Servidor Local - Ventanilla Digital Uriangato
cd /d "%~dp0"
set "PHPRC=%~dp0php82"
set "PATH=%~dp0php82;%PATH%"

echo =======================================================
echo   Iniciando Servidor de Desarrollo CodeIgniter 4
echo   Ventanilla Digital de Movilidad - Uriangato
echo =======================================================
echo.
echo Servidor listo en: http://localhost:8080
echo Presiona Ctrl+C para detener el servidor.
echo.

"%~dp0php82\php.exe" spark serve --php="%~dp0php82\php.exe"
pause
