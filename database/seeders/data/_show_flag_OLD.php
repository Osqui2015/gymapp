<?php
$j = json_decode(file_get_contents(__DIR__ . '/exercises.json'), true);

// Mostrar info completa de "flag" y similares
echo "=== FLAG id=3303 (detalle) ===\n";
foreach ($j as $ex) {
    if ($ex['id'] === '3303') {
        print_r([
            'name' => $ex['name'],
            'cat' => $ex['category'],
            'body_part' => $ex['body_part'],
            'target' => $ex['target'],
            'eq' => $ex['equipment'],
            'instr' => $ex['instructions'],
        ]);
        break;
    }
}

echo "\n=== Buscar: 'sit-up' y 'decline' ===\n";
foreach ($j as $ex) {
    $name = strtolower($ex['name']);
    if (strpos($name, 'decline') !== false && $ex['category'] === 'waist') {
        echo $ex['id'] . " | " . $ex['name'] . " | eq=" . $ex['equipment'] . " | target=" . $ex['target'] . "\n";
    }
}
