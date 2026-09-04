#!/usr/bin/env node
/**
 * Patcher masivo: reemplaza toLocaleDateString(...) por helpers del
 * composable useFormatters en todos los .vue de resources/js.
 *
 * Reglas:
 *   'es-ES'/'es-AR'/'es-MX' con { day: '2-digit', month: '2-digit', year: 'numeric' }
 *      → formatDate(d)  (dd/mm/yyyy)
 *   { day: '2-digit', month: 'short' [, year: 'numeric'] }
 *      → formatDateShort / formatDateMedium
 *   { day: '2-digit', month: 'long', year: 'numeric' }
 *      → formatDateLong(d)
 *   { month: 'short' } (solo mes)
 *      → formatMonthShort(d)
 *
 * Si el archivo usa helpers pero no tiene `useFormatters`, le agrega
 * el import y el destructure correspondiente.
 */
const fs = require('fs');
const path = require('path');

const ROOT = 'I:\\laragon\\www\\GymApp\\resources\\js';

const PATTERNS = [
  // dd/mm/yyyy con year
  {
    regex: /\.toLocaleDateString\(\s*'es-(?:ES|AR|MX)',\s*\{\s*day:\s*'2-digit',\s*month:\s*'2-digit',\s*year:\s*'numeric'\s*\}\s*\)/g,
    helper: 'formatDate',
  },
  {
    regex: /\.toLocaleDateString\(\s*'es-(?:ES|AR|MX)',\s*\{\s*year:\s*'numeric',\s*month:\s*'2-digit',\s*day:\s*'2-digit'\s*\}\s*\)/g,
    helper: 'formatDate',
  },
  // dd MMM yyyy (con year)
  {
    regex: /\.toLocaleDateString\(\s*'es-(?:ES|AR|MX)',\s*\{\s*day:\s*'2-digit',\s*month:\s*'short',\s*year:\s*'(?:numeric|2-digit)'\s*\}\s*\)/g,
    helper: 'formatDateMedium',
  },
  // dd MMM (sin year)
  {
    regex: /\.toLocaleDateString\(\s*'es-(?:ES|AR|MX)',\s*\{\s*day:\s*'2-digit',\s*month:\s*'short'\s*\}\s*\)/g,
    helper: 'formatDateShort',
  },
  // dd de mes de yyyy (largo)
  {
    regex: /\.toLocaleDateString\(\s*'es-(?:ES|AR|MX)',\s*\{\s*day:\s*'2-digit',\s*month:\s*'long',\s*year:\s*'numeric'\s*\}\s*\)/g,
    helper: 'formatDateLong',
  },
  // Solo mes corto
  {
    regex: /\.toLocaleDateString\(\s*'es-(?:ES|AR|MX)',\s*\{\s*month:\s*'short'\s*\}\s*\)/g,
    helper: 'formatMonthShort',
  },
];

function walk(dir, out = []) {
  for (const f of fs.readdirSync(dir, { withFileTypes: true })) {
    const full = path.join(dir, f.name);
    if (f.isDirectory()) walk(full, out);
    else if (f.name.endsWith('.vue')) out.push(full);
  }
  return out;
}

function processFile(file) {
  const original = fs.readFileSync(file, 'utf8');
  let text = original;
  const usedHelpers = new Set();

  for (const { regex, helper } of PATTERNS) {
    text = text.replace(regex, (_, inner) => {
      usedHelpers.add(helper);
      // inner es la expresión de la fecha, ej: 'date' o 'd'
      return `${helper}(${inner})`;
    });
  }

  if (text === original) return false;

  // Si no tiene import de useFormatters pero usa helpers, lo agregamos
  if (usedHelpers.size > 0 && !text.includes('useFormatters')) {
    // Inyectar destructure + import
    const helpersList = Array.from(usedHelpers).sort().join(', ');
    const m = text.match(/<script\s+setup[^>]*>\s*/);
    if (m) {
      const insertAt = m.index + m[0].length;
      text = text.slice(0, insertAt) + `const { ${helpersList} } = useFormatters();\n    ` + text.slice(insertAt);
    }
    // Agregar el import después del último import
    const imports = [...text.matchAll(/^import\s.+;$/gm)];
    if (imports.length > 0) {
      const last = imports[imports.length - 1];
      text = text.slice(0, last.index + last[0].length)
        + "\nimport { useFormatters } from '@/composables/useFormatters';"
        + text.slice(last.index + last[0].length);
    }
  } else if (usedHelpers.size > 0 && text.includes('useFormatters')) {
    // Ya tiene useFormatters: ver si destructuró los helpers
    const destMatch = text.match(/const\s*\{\s*([^}]+)\s*\}\s*=\s*useFormatters\s*\(\s*\)/);
    const existing = new Set();
    if (destMatch) {
      destMatch[1].split(',').forEach(h => existing.add(h.trim()));
    }
    const missing = Array.from(usedHelpers).filter(h => !existing.has(h));
    if (missing.length > 0) {
      const allHelpers = Array.from(new Set([...existing, ...missing])).sort().join(', ');
      if (destMatch) {
        text = text.replace(
          /const\s*\{\s*[^}]+\s*\}\s*=\s*useFormatters\s*\(\s*\)/,
          `const { ${allHelpers} } = useFormatters()`
        );
      } else {
        const m = text.match(/<script\s+setup[^>]*>\s*/);
        if (m) {
          const insertAt = m.index + m[0].length;
          text = text.slice(0, insertAt) + `const { ${allHelpers} } = useFormatters();\n    ` + text.slice(insertAt);
        }
      }
    }
  }

  if (text !== original) {
    fs.writeFileSync(file, text, 'utf8');
    return true;
  }
  return false;
}

const files = walk(ROOT);
const modified = files.filter(processFile);
console.log(`Archivos modificados: ${modified.length}`);
modified.forEach(f => console.log('  ' + f.replace(ROOT + '\\', '')));
