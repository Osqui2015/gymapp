<?php
require __DIR__.'/../../../vendor/autoload.php';
$app = require_once __DIR__.'/../../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\EjercicioController;
use App\Models\Ejercicio;

$ref = new ReflectionClass(EjercicioController::class);
$aliases = $ref->getConstant('NAME_ALIASES');

$tests = [
    'Buenos días (Good Mornings)',
    'Aperturas / Cruces en polea alta',
    'Extensión de tríceps en el suelo (Skullcrusher)',
    'Sentadilla Hack o Péndulo',
];

foreach ($tests as $t) {
    $normalized = EjercicioController::normalize($t);
    echo "Input: '$t'\n";
    echo "  normalized: '$normalized'\n";
    echo "  alias match: " . (isset($aliases[$normalized]) ? $aliases[$normalized] : '(no)') . "\n";

    // Buscar si hay match literal exacto en la tabla ejercicios
    $exact = Ejercicio::where('nombre', $t)->first();
    echo "  exact match in DB: " . ($exact ? "id={$exact->id} source={$exact->source}" : '(no)') . "\n";

    echo "\n";
}
