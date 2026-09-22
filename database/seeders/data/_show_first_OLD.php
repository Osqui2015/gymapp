<?php
$j = json_decode(file_get_contents(__DIR__ . '/exercises.json'), true);
echo "Total: " . count($j) . PHP_EOL;
echo "First: " . $j[0]['name'] . " | category=" . $j[0]['category'] . PHP_EOL;

// Categorías distintas
$cats = [];
foreach ($j as $ex) {
    $cats[$ex['category']] = ($cats[$ex['category']] ?? 0) + 1;
}
echo "Categorías:\n";
foreach ($cats as $c => $n) {
    echo "  $c => $n\n";
}
