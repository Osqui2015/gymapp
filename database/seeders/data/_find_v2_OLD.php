<?php
$j = json_decode(file_get_contents(__DIR__ . '/exercises.json'), true);

// Todos los cardio (29)
echo "=== CARDIO (29) ===\n";
foreach ($j as $ex) {
    if ($ex['category'] === 'cardio') {
        echo $ex['id'] . " | " . $ex['name'] . " | eq=" . $ex['equipment'] . "\n";
    }
}

// Buscar "ab" / "core" / "core" - dragon flag es core
echo "\n=== WAIST (169) - primeros 50 ===\n";
$i = 0;
foreach ($j as $ex) {
    if ($ex['category'] === 'waist') {
        echo $ex['id'] . " | " . $ex['name'] . " | eq=" . $ex['equipment'] . " | target=" . $ex['target'] . "\n";
        $i++;
        if ($i >= 50) break;
    }
}
