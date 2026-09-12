#!/usr/bin/env bash
# cleanup-old-build.sh
# Borra archivos de public/build/assets/ que NO estan en el manifest actual.
# Equivalente al cleanup-old-build.ps1 pero para Linux/Mac.
#
# Uso (en el server, despues de subir el nuevo public/build/):
#   bash scripts/cleanup-old-build.sh [--dry-run]
#
# Ejemplos:
#   bash scripts/cleanup-old-build.sh --dry-run
#   bash scripts/cleanup-old-build.sh

set -e

DRY_RUN=false
if [ "$1" = "--dry-run" ] || [ "$1" = "-n" ]; then
    DRY_RUN=true
fi

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
ASSETS_DIR="$PROJECT_ROOT/public/build/assets"
MANIFEST="$PROJECT_ROOT/public/build/manifest.json"

if [ ! -f "$MANIFEST" ]; then
    echo "ERROR: no se encontro el manifest en $MANIFEST. Hiciste npm run build?" >&2
    exit 1
fi

if [ ! -d "$ASSETS_DIR" ]; then
    echo "ERROR: no se encontro la carpeta de assets en $ASSETS_DIR." >&2
    exit 1
fi

# Extraer los archivos referenciados del manifest (formato v3 de Vite).
# Cada entrada tiene "file": "assets/Foo.js". Normalizamos a "Foo.js".
REFERENCED=$(grep -oE '"file":\s*"assets/[^"]+"' "$MANIFEST" | sed -E 's|"file":\s*"assets/||;s|"$||' | sort -u)

REFERENCED_COUNT=$(echo "$REFERENCED" | grep -c . || true)
echo "Archivos en manifest: $REFERENCED_COUNT"
echo "Carpeta: $ASSETS_DIR"
echo ""

# Listar archivos en disco.
ON_DISK=$(ls "$ASSETS_DIR" 2>/dev/null || true)
DISK_COUNT=$(echo "$ON_DISK" | grep -c . || true)

# Calcular huerfanos: en disco pero no en manifest.
ORPHANS=$(comm -23 <(echo "$ON_DISK" | sort -u) <(echo "$REFERENCED" | sort -u) | grep -v '^$' || true)

ORPHAN_COUNT=$(echo -n "$ORPHANS" | grep -c . || true)

if [ "$ORPHAN_COUNT" -eq 0 ]; then
    echo "OK: no hay assets huerfanos. Nada que borrar."
    exit 0
fi

echo "Assets huerfanos encontrados: $ORPHAN_COUNT"
echo "$ORPHANS" | while read -r f; do
    [ -n "$f" ] && echo "  - $f"
done
echo ""

if [ "$DRY_RUN" = true ]; then
    echo "[DRY RUN] No se borro nada. Sacar --dry-run para borrar de verdad."
    exit 0
fi

echo -n "Borrar $ORPHAN_COUNT archivo(s)? (s/N) "
read -r CONFIRM
if [ "$CONFIRM" != "s" ] && [ "$CONFIRM" != "S" ]; then
    echo "Cancelado."
    exit 0
fi

DELETED=0
echo "$ORPHANS" | while read -r f; do
    [ -z "$f" ] && continue
    if rm -f "$ASSETS_DIR/$f"; then
        echo "Borrado: $f"
    else
        echo "WARN: no se pudo borrar $f" >&2
    fi
done

echo ""
echo "Listo."
