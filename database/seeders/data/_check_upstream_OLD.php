<?php
// Comparar upstream vs local para ver si son diferentes
$local = json_decode(file_get_contents(__DIR__ . '/exercises.json'), true);
$up = json_decode(file_get_contents(__DIR__ . '/_vg_upstream.json'), true);

echo "Local: " . count($local) . " | Upstream: " . count($up) . PHP_EOL;

// Búsqueda exhaustiva en upstream
$queries = ['dragon', 'rowing', 'erg', 'concept', 'c2', 'row machine', 'flag'];

foreach ($queries as $q) {
    $matches = [];
    foreach ($up as $ex) {
        $name = strtolower($ex['name']);
        if (strpos($name, $q) !== false) {
            $matches[] = $ex['id'] . ' | ' . $ex['name'] . ' | cat=' . $ex['category'] . ' | eq=' . $ex['equipment'];
        }
    }
    if (count($matches) > 0) {
        echo "=== upstream '$q' (" . count($matches) . ") ===\n";
        foreach (array_slice($matches, 0, 10) as $m) echo $m . "\n";
        echo "\n";
    } else {
        echo "=== upstream '$q' -> 0 ===\n";
    }
}
