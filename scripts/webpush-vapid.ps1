# ============================================================
# webpush-vapid.ps1
# ============================================================
# Wrapper que setea OPENSSL_CONF antes de invocar artisan.
# Necesario en Laragon (Windows) porque C:\Program Files\Common
# Files\SSL\openssl.cnf no existe y PHP no puede generar EC P-256
# sin un config file válido. Apunta al que trae Git for Windows.
#
# Uso:
#   .\scripts\webpush-vapid.ps1
#   .\scripts\webpush-vapid.ps1 --show
#
# Equivalente a:  php artisan webpush:vapid [--show]
# ============================================================

$ErrorActionPreference = 'Stop'

$php = "I:\laragon\bin\php\php-8.2.30-Win32-vs16-x64\php.exe"
$artisan = Join-Path $PSScriptRoot "..\artisan"

# Buscar el primer openssl.cnf que exista
$opensslConfCandidates = @(
    "C:\Program Files\Git\usr\ssl\openssl.cnf",
    "C:\Program Files\Git\mingw64\etc\ssl\openssl.cnf",
    "C:\Program Files\Git\etc\ssl\openssl.cnf"
)

$found = $false
foreach ($candidate in $opensslConfCandidates) {
    if (Test-Path $candidate) {
        $env:OPENSSL_CONF = $candidate
        Write-Host "OPENSSL_CONF=$candidate" -ForegroundColor DarkGray
        $found = $true
        break
    }
}

if (-not $found) {
    Write-Warning "No se encontró openssl.cnf en ninguna ruta conocida."
    Write-Warning "Probá instalando Git for Windows o pasando OPENSSL_CONF manualmente."
}

& $php $artisan webpush:vapid @args
exit $LASTEXITCODE
