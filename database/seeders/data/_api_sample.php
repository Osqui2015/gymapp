<?php
require __DIR__ . '/../../../vendor/autoload.php';
$app = require __DIR__ . '/../../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$req = \Illuminate\Http\Request::create('/api/rutinas', 'GET');
$req->setUserResolver(fn () => User::find(1));
$ctrl = new \App\Http\Controllers\RutinaController();
$resp = $ctrl->index($req);
$data = json_decode($resp->getContent(), true);

// Sample de las primeras rutinas oficiales
echo "=== Primeras 5 rutinas con niveles/keys ===\n";
$sample = [];
foreach ($data as $r) {
    if (!isset($sample[$r['nivel']])) {
        $sample[$r['nivel']] = $r;
    }
    if (count($sample) === 3) break;
}
foreach ($sample as $nivel => $r) {
    echo "\n$nivel:\n";
    foreach (['id', 'nivel', 'modalidad', 'dia', 'ejercicio_nombre', 'series', 'reps_min', 'orden', 'is_favorita', 'created_by'] as $k) {
        echo "  $k: " . ($r[$k] ?? 'NULL') . "\n";
    }
}

// Verificar modalidades distintas para "Principiante"
echo "\n=== Modalidades distintas para Principiante ===\n";
$mods = [];
foreach ($data as $r) {
    if ($r['nivel'] === 'Principiante') {
        $mods[$r['modalidad']] = true;
    }
}
foreach (array_keys($mods) as $m) {
    echo "  '$m'\n";
}

// Verificar días distintos para "Principiante 2 Días"
echo "\n=== Días para 'Principiante 2 Días' ===\n";
$dias = [];
foreach ($data as $r) {
    if ($r['nivel'] === 'Principiante' && $r['modalidad'] === '2 Días') {
        $dias[$r['dia']] = true;
    }
}
foreach (array_keys($dias) as $d) {
    echo "  '$d'\n";
}
