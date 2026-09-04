#!/usr/bin/env python3
"""
Patcher masivo: reemplaza toLocaleDateString(...) por helpers del composable
useFormatters en todos los .vue de resources/js.

Reglas:
  - 'es-ES'/'es-AR'/'es-MX'  con { day: '2-digit', month: '2-digit', year: 'numeric' }
      → formatDate(d)  (dd/mm/yyyy)
  - { day: '2-digit', month: 'short' [, year: 'numeric'] }
      → formatDateMedium(d) si tiene year, formatDateShort(d) si no
  - { day: '2-digit', month: 'long', year: 'numeric' }
      → formatDateLong(d)
  - { month: 'short' } (solo mes)
      → formatMonthShort(d)

NO toca archivos que ya importan useFormatters y usan formatDate directamente.
"""
import re
import sys
from pathlib import Path

ROOT = Path(r"I:\laragon\www\GymApp\resources\js")
IMPORTS_TO_ADD = "import { useFormatters } from '@/composables/useFormatters';"  # fallback

# (pattern, replacement, needs_destructuring)
# needs_destructuring=True significa que el archivo debe agregar:
#   const { formatDate, formatDateShort, formatDateMedium, formatDateLong, formatMonthShort } = useFormatters();
# si no las tiene ya.
PATTERNS = [
    # dd/mm/yyyy con year
    (
        r"\.toLocaleDateString\(\s*'es-(?:ES|AR|MX)',\s*\{\s*day:\s*'2-digit',\s*month:\s*'2-digit',\s*year:\s*'numeric'\s*\}\s*\)",
        "formatDate",
        True,
    ),
    (
        r"\.toLocaleDateString\(\s*'es-(?:ES|AR|MX)',\s*\{\s*year:\s*'numeric',\s*month:\s*'2-digit',\s*day:\s*'2-digit'\s*\}\s*\)",
        "formatDate",
        True,
    ),
    # dd MMM yyyy (con year)
    (
        r"\.toLocaleDateString\(\s*'es-(?:ES|AR|MX)',\s*\{\s*day:\s*'2-digit',\s*month:\s*'short',\s*year:\s*'(?:numeric|2-digit)'\s*\}\s*\)",
        "formatDateMedium",
        True,
    ),
    # dd MMM (sin year)
    (
        r"\.toLocaleDateString\(\s*'es-(?:ES|AR|MX)',\s*\{\s*day:\s*'2-digit',\s*month:\s*'short'\s*\}\s*\)",
        "formatDateShort",
        True,
    ),
    # dd de mes de yyyy (largo)
    (
        r"\.toLocaleDateString\(\s*'es-(?:ES|AR|MX)',\s*\{\s*day:\s*'2-digit',\s*month:\s*'long',\s*year:\s*'numeric'\s*\}\s*\)",
        "formatDateLong",
        True,
    ),
    # Solo mes corto
    (
        r"\.toLocaleDateString\(\s*'es-(?:ES|AR|MX)',\s*\{\s*month:\s*'short'\s*\}\s*\)",
        "formatMonthShort",
        True,
    ),
]


def process_file(path: Path) -> bool:
    """Returns True if the file was modified."""
    text = path.read_text(encoding="utf-8")
    original = text
    used_helpers = set()

    for pattern, repl, _ in PATTERNS:
        def replace_call(m):
            inner = m.group(1)  # the date expression
            used_helpers.add(repl)
            return f"{repl}({inner})"
        text = re.sub(pattern, replace_call, text)

    if text == original:
        return False

    # Si usa helpers y no los tiene ya destructurados ni importados, los agregamos
    if used_helpers:
        # Si ya importa useFormatters, no hacemos nada extra
        if "useFormatters" not in text:
            # No tocamos el import; el composable ya existe y se puede usar
            # solo si el archivo tiene <script setup>. Asumimos que sí.
            # Agregamos la destructuración al inicio del script setup.
            helpers_list = ", ".join(sorted(used_helpers))
            # Buscar <script setup> y agregar la línea después
            m = re.search(r"(<script\s+setup[^>]*>\s*)", text)
            if m:
                insertion = (
                    f"const {{ {helpers_list} }} = useFormatters();\n    "
                )
                text = text[: m.end()] + insertion + text[m.end() :]
            # Si no hay script setup, no podemos inyectar; lo dejamos
            # (en este proyecto todos los .vue con fechas tienen <script setup>)
        else:
            # Ya importa useFormatters; chequeamos si destructuró los helpers
            dest_match = re.search(
                r"const\s*\{\s*([^}]+)\s*\}\s*=\s*useFormatters\s*\(\s*\)", text
            )
            existing_helpers = set()
            if dest_match:
                existing_helpers = {
                    h.strip() for h in dest_match.group(1).split(",")
                }
            missing = used_helpers - existing_helpers
            if missing:
                # Agregar al destructure existente o crear uno nuevo
                if dest_match:
                    new_helpers = ", ".join(sorted(existing_helpers | missing))
                    text = re.sub(
                        r"const\s*\{\s*[^}]+\s*\}\s*=\s*useFormatters\s*\(\s*\)",
                        f"const {{ {new_helpers} }} = useFormatters()",
                        text,
                        count=1,
                    )
                else:
                    m = re.search(r"(<script\s+setup[^>]*>\s*)", text)
                    if m:
                        insertion = (
                            f"const {{ {', '.join(sorted(missing))} }} = "
                            f"useFormatters();\n    "
                        )
                        text = text[: m.end()] + insertion + text[m.end() :]

    # Si el archivo importa useFormatters pero no lo tenía, agregamos el import
    if used_helpers and "useFormatters" not in original and "useFormatters" in text:
        # Ya lo inyectamos arriba en el body; falta el import.
        # Lo agregamos después de la línea de imports.
        m = re.search(r"(^import\s+.+;$)", text, re.MULTILINE)
        if m:
            last_import_end = 0
            for im in re.finditer(r"^import\s.+;$", text, re.MULTILINE):
                last_import_end = im.end()
            text = (
                text[:last_import_end]
                + "\nimport { useFormatters } from '@/composables/useFormatters';"
                + text[last_import_end:]
            )

    if text != original:
        path.write_text(text, encoding="utf-8")
        return True
    return False


def main():
    if not ROOT.exists():
        print(f"ERROR: root {ROOT} no existe", file=sys.stderr)
        sys.exit(1)

    vue_files = list(ROOT.rglob("*.vue"))
    modified = []
    for f in vue_files:
        if process_file(f):
            modified.append(f)

    print(f"Archivos modificados: {len(modified)}")
    for f in modified:
        print(f"  {f.relative_to(ROOT)}")


if __name__ == "__main__":
    main()
