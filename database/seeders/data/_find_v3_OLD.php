<?php
$j = json_decode(file_get_contents(__DIR__ . '/exercises.json'), true);

// Búsquedas más amplias
$queries = [
    'row', 'bike', 'cycle', 'elliptical', 'ergometer', 'c2',
    'flag', 'reverse', 'tuck', 'plank', 'incline sit',
    'ski', 'paddle', 'boat',
];

foreach ($queries as $q) {
    $matches = [];
    foreach ($j as $ex) {
        $name = strtolower($ex['name']);
        if (strpos($name, $q) !== false) {
            $matches[] = $ex['id'] . ' | ' . $ex['name'] . ' | cat=' . $ex['category'] . ' | eq=' . $ex['equipment'];
        }
    }
    if (count($matches) > 0 && count($matches) < 30) {
        echo "=== '$q' (" . count($matches) . ") ===\n";
        foreach ($matches as $m) echo $m . "\n";
        echo "\n";
    } elseif (count($matches) >= 30) {
        echo "=== '$q' (" . count($matches) . ") ===\n";
        foreach (array_slice($matches, 0, 10) as $m) echo $m . "\n";
        echo "  ... truncated\n\n";
    }
}
