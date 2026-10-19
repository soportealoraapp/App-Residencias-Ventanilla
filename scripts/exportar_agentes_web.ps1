<#
.SYNOPSIS
    Reconstruye el bundle web de la App Agentes de Transito (Expo -> public/agentes).
.DESCRIPTION
    Repite en local los tres pasos que ejecuta scripts/vercel-install.sh en el
    despliegue, porque "expo export --clear" por si solo deja el artifact incompleto:
      1. Exporta el bundle web con las variables de entorno de mobile/.env.
      2. Copia las fuentes de iconos a public/agentes/fonts (el export las borra).
      3. Localiza a espanol el shell HTML que Expo genera en ingles.
    Si despues de este script "git status public/agentes" sale vacio, el bundle
    del repositorio ya coincidia con el codigo fuente.
.NOTES
    El texto de este archivo es ASCII a proposito: Windows PowerShell 5.1 lee los
    .ps1 sin BOM como ANSI y romperian cualquier acento escrito aqui.
#>
[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'

$raiz    = Split-Path -Parent $PSScriptRoot
$app     = Join-Path $raiz 'mobile\agentes-transito'
$salida  = Join-Path $raiz 'public\agentes'
$fuentes = Join-Path $app 'node_modules\@expo\vector-icons\build\vendor\react-native-vector-icons\Fonts\*.ttf'

if (-not (Test-Path -LiteralPath (Join-Path $app 'node_modules'))) {
    throw 'Falta mobile/agentes-transito/node_modules. Ejecuta: npm install --legacy-peer-deps'
}

Push-Location -LiteralPath $app
try {
    Write-Output '[1/3] expo export (web)'
    npx expo export -p web --output-dir ..\..\public\agentes --clear
    if ($LASTEXITCODE -ne 0) { throw "expo export fallo con codigo $LASTEXITCODE" }
}
finally {
    Pop-Location
}

Write-Output '[2/3] fuentes de iconos'
$destino = Join-Path $salida 'fonts'
New-Item -ItemType Directory -Force -Path $destino | Out-Null
$copiadas = @(Get-ChildItem -Path $fuentes -ErrorAction SilentlyContinue)
if ($copiadas.Count -eq 0) {
    throw "No se encontro ninguna fuente en $fuentes; el package de iconos cambio de ruta."
}
Copy-Item -Path $fuentes -Destination $destino -Force
Write-Output ("       " + $copiadas.Count + " archivos .ttf en public/agentes/fonts")

Write-Output '[3/3] shell HTML en espanol'
$php = Join-Path $raiz 'php82\php.exe'
if (-not (Test-Path -LiteralPath $php)) { $php = 'php' }
& $php (Join-Path $raiz 'scripts\localizar_shell_agentes.php')
if ($LASTEXITCODE -ne 0) { throw "localizar_shell_agentes.php fallo con codigo $LASTEXITCODE" }

Write-Output 'Listo. Revisa git status public/agentes antes de continuar.'
