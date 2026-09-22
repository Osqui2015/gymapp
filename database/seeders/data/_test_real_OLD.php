<?php
require __DIR__ . '/../../../vendor/autoload.php';
$app = require __DIR__ . '/../../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Ejercicio;
use Illuminate\Http\Request;

// Llamar el endpoint real usando el container
$tests = [
    'Dragon Flags',
    'Remo Ergómetro (Rowing Machine)',
    'Press de banca inclinado',
    'Press de banca plano pesado',
    'Press de banca',
    'Sentadilla',
    'Curl de bíceps con barra Z',
    'Buenos días (Good Mornings)',
    'Hip thrust',
    'Bicicleta de spinning',
    'Estática',
];

$controller = app(\App\Http\Controllers\EjercicioController::class);

foreach ($tests as $name) {
    $req = Request::create('/api/ejercicios/media', 'GET', ['name' => $name]);
    try {
        $resp = $controller->ejercicioMediaByName($req);
        $data = $resp->getData(true);
        $src = $data['source'] ?? '?';
        $match = $data['match_type'] ?? '?';
        $matched = $data['nombre'] ?? '?';
        echo sprintf("%-40s → [%s] %s (match=%s)\n", $name, $src, $matched, $match);
    } catch (\Throwable $e) {
        echo sprintf("%-40s → ERROR: %s\n", $name, $e->getMessage());
    }
}
