<?php

/**
 * Lee el CSV de mapeo legacy → VisualGym y genera un array PHP listo
 * para pegar en NAME_ALIASES.
 *
 * Input:  C:\Users\posca\Downloads\visualgym_legacy_mapping.csv
 *         (también acepta el .json si existe)
 *
 * Output: PHP array formateado, ordenado alfabéticamente.
 */

$csvPath = 'C:\Users\posca\Downloads\visualgym_legacy_mapping.csv';
$jsonPath = 'C:\Users\posca\Downloads\visualgym_legacy_mapping.json';

$rows = [];
if (file_exists($csvPath)) {
    $fh = fopen($csvPath, 'r');
    // Skip BOM si existe
    $bom = fread($fh, 3);
    if ($bom !== "\xEF\xBB\xBF") {
        rewind($fh);
    }
    $header = fgetcsv($fh);
    // Normalizar nombres de columnas
    $header = array_map(fn ($h) => trim(strtolower($h)), $header);
    while (($row = fgetcsv($fh)) !== false) {
        $assoc = array_combine($header, $row);
        $rows[] = $assoc;
    }
    fclose($fh);
} elseif (file_exists($jsonPath)) {
    $data = json_decode(file_get_contents($jsonPath), true);
    $rows = $data['rows'] ?? $data ?? [];
} else {
    fwrite(STDERR, "ERROR: ni CSV ni JSON encontrados\n");
    exit(1);
}

echo "Total legacy leídos: " . count($rows) . "\n\n";

// Agrupar por legacy_normalizado → primer visualgym_nombre no vacío
$mapping = [];
$noMatch = 0;
foreach ($rows as $r) {
    $legacyKey = trim($r['legacy_normalizado'] ?? '');
    if ($legacyKey === '') continue;

    $vgName = trim($r['visualgym_nombre'] ?? '');
    $vgId = trim($r['visualgym_id'] ?? '');

    if (!isset($mapping[$legacyKey])) {
        // Primer registro: si tiene match, lo guardamos; si no, string vacío
        $mapping[$legacyKey] = ($vgName !== '' && $vgId !== '') ? $vgName : '';
        if ($mapping[$legacyKey] === '') $noMatch++;
    }
    // Si ya estaba seteado y el nuevo tiene match pero el viejo no, mejoramos
    elseif ($mapping[$legacyKey] === '' && $vgName !== '' && $vgId !== '') {
        $mapping[$legacyKey] = $vgName;
        $noMatch--;
    }
}

// Ordenar alfabéticamente
ksort($mapping);

$matched = count(array_filter($mapping, fn($v) => $v !== ''));
$unmatched = count(array_filter($mapping, fn($v) => $v === ''));

echo "Aliases únicos: " . count($mapping) . "\n";
echo "Con match VisualGym: $matched\n";
echo "Sin match (legacy fallback): $unmatched\n\n";

echo "=== Array PHP para NAME_ALIASES ===\n\n";

echo "    private const NAME_ALIASES = [\n";
foreach ($mapping as $k => $v) {
    // Escapar comillas simples si aparecen
    $kEsc = str_replace("'", "\\'", $k);
    if ($v === '') {
        echo "        '" . $kEsc . "' => '',\n";
    } else {
        $vEsc = str_replace("'", "\\'", $v);
        echo "        '" . $kEsc . "' => '" . $vEsc . "',\n";
    }
}
echo "    ];\n";
