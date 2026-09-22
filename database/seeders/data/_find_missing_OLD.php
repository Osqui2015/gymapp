<?php
// Buscar matches para los legacy sin VisualGym exacto
$json = json_decode(file_get_contents(__DIR__ . '/exercises.json'), true);

$queries = [
    'dragon flag',
    'dragon flags',
    'rowing machine',
    'rowing ergometer',
    'concept 2',
    'rowing',
];

foreach ($queries as $q) {
    echo "=== Buscando: $q ===\n";
    $matches = [];
    foreach ($json as $ex) {
        $name = strtolower($ex['name'] ?? '');
        if (strpos($name, $q) !== false) {
            $matches[] = $ex['id'] . ' | ' . $ex['name'] . ' | target=' . ($ex['target'] ?? '?') . ' | body=' . ($ex['bodyPart'] ?? '?') . ' | eq=' . ($ex['equipment'] ?? '?');
        }
    }
    foreach (array_slice($matches, 0, 15) as $m) {
        echo $m . "\n";
    }
    echo "Total: " . count($matches) . "\n\n";
}
