$ErrorActionPreference = 'Stop'

$php = Join-Path $PSScriptRoot '..\php82\php.exe'
$phpIni = Join-Path $PSScriptRoot '..\php82\php.ini-development'
$extensionDir = Join-Path $PSScriptRoot '..\php82\ext'
$phpunit = Join-Path $PSScriptRoot '..\vendor\bin\phpunit'

if (-not (Test-Path $php)) {
    throw "No se encontro el PHP incluido en php82."
}

& $php -c $phpIni `
    -d "extension_dir=$extensionDir" `
    -d extension=sqlite3 `
    -d extension=pdo_sqlite `
    -d extension=mbstring `
    -d extension=intl `
    -d extension=fileinfo `
    -d extension=curl `
    -d extension=gd `
    $phpunit -c (Join-Path $PSScriptRoot '..\phpunit.xml.dist') @args

exit $LASTEXITCODE
