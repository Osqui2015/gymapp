# cleanup-old-build.ps1
# Borra archivos de public/build/assets/ que NO estan en el manifest actual.
# Sirve para evitar acumular hashes viejos despues de cada deploy.
#
# Uso (en el server, despues de subir el nuevo public/build/):
#   pwsh -NoProfile -File scripts/cleanup-old-build.ps1
#
# Opciones:
#   -DryRun   Solo muestra que borraria, sin tocar nada.
#   -Verbose  Muestra cada archivo procesado.
#
# Ejemplo:
#   pwsh -NoProfile -File scripts/cleanup-old-build.ps1 -DryRun
#   pwsh -NoProfile -File scripts/cleanup-old-build.ps1

[CmdletBinding()]
param(
    [switch]$DryRun,
    [switch]$Force
)

$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $PSScriptRoot
$assetsDir = Join-Path $projectRoot 'public\build\assets'
$manifestPath = Join-Path $projectRoot 'public\build\manifest.json'

if (-not (Test-Path $manifestPath)) {
    Write-Error "No se encontro el manifest en $manifestPath. Hiciste npm run build?"
    exit 1
}

if (-not (Test-Path $assetsDir)) {
    Write-Error "No se encontro la carpeta de assets en $assetsDir."
    exit 1
}

# Parsear el manifest para extraer los archivos referenciados.
$manifest = Get-Content -Raw -Path $manifestPath | ConvertFrom-Json
$referencedFiles = @()

# Manifest v3 de Vite (formato nuevo): cada entrada tiene "file" (path dentro de assets/).
foreach ($prop in $manifest.PSObject.Properties) {
    $value = $prop.Value
    if ($value.file) {
        # Normalizar: el manifest dice "assets/Foo.js", queremos solo "Foo.js" para comparar.
        $normalized = $value.file -replace '^assets/', ''
        $referencedFiles += $normalized
    }
}

Write-Host "Archivos en manifest: $($referencedFiles.Count)"
Write-Host "Carpeta: $assetsDir"
Write-Host ""

# Listar archivos reales en la carpeta.
$onDisk = Get-ChildItem -Path $assetsDir -File | Select-Object -ExpandProperty Name

# Calcular huerfanos: en disco pero no en manifest.
$orphans = $onDisk | Where-Object { $_ -notin $referencedFiles }

if ($orphans.Count -eq 0) {
    Write-Host "OK: no hay assets huerfanos. Nada que borrar."
    exit 0
}

Write-Host "Assets huerfanos encontrados: $($orphans.Count)"
$orphans | ForEach-Object { Write-Host "  - $_" }
Write-Host ""

if ($DryRun) {
    Write-Host "[DRY RUN] No se borro nada. Sacar -DryRun para borrar de verdad."
    exit 0
}

if (-not $Force) {
    $confirm = Read-Host "Borrar $($orphans.Count) archivo(s)? (s/N)"
    if ($confirm -notin @('s', 'S', 'si', 'SI', 'Si', 'y', 'Y', 'yes', 'YES', 'Yes')) {
        Write-Host "Cancelado."
        exit 0
    }
}

foreach ($file in $orphans) {
    $path = Join-Path $assetsDir $file
    try {
        Remove-Item -Path $path -Force
        Write-Host "Borrado: $file"
    } catch {
        Write-Warning "No se pudo borrar $file : $_"
    }
}

Write-Host ""
Write-Host "Listo. $($orphans.Count) archivo(s) eliminado(s)."
