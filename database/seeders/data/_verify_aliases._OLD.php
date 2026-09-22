<?php
require __DIR__.'/../../../vendor/autoload.php';
$app = require_once __DIR__.'/../../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Ejercicio;

echo "=== Test normalize + aliases ===\n";
$names = [
    'Sentadilla Bulgara',
    'Press de hombros',
    'Press militar',
    'Hip thrust pesado',
    'Remo con mancuerna a una mano',
    'Curl de biceps con barra Z',
];

foreach ($names as $n) {
    $norm = \App\Http\Controllers\EjercicioController::normalize($n);
    echo "'$n' -> '$norm'\n";
    $alias = null;
    $ref = new \ReflectionClass(\App\Http\Controllers\EjercicioController::class);
    $aliases = $ref->getConstant('NAME_ALIASES');
    if (isset($aliases[$norm])) {
        $alias = $aliases[$norm];
        echo "  alias exacto: '$alias'\n";
    } else {
        echo "  (sin alias exacto)\n";
    }
}

echo "\n=== Buscar nombres VisualGym que faltan ===\n";
$targets = ['barbell bulgarian split squat', 'barbell shoulder press', 'barbell military press', 'barbell hip thrust', 'dumbbell one arm row', 'ez bar curl', 'sled 45° leg press'];
foreach ($targets as $t) {
    $count = Ejercicio::where('source', 'visualgym')->where('nombre', $t)->count();
    echo "  '$t': $count registros\n";
}

echo "\n=== Buscar 'barbell press' ===\n";
$ej = Ejercicio::where('source', 'visualgym')->where('nombre', 'like', '%barbell press%')->take(10)->get(['nombre']);
foreach ($ej as $e) {
    echo "  - " . $e->nombre . "\n";
}

echo "\n=== Buscar 'military' ===\n";
$ej = Ejercicio::where('source', 'visualgym')->where('nombre', 'like', '%military%')->take(10)->get(['nombre']);
foreach ($ej as $e) {
    echo "  - " . $e->nombre . "\n";
}

echo "\n=== Buscar 'bulgarian' ===\n";
$ej = Ejercicio::where('source', 'visualgym')->where('nombre', 'like', '%bulgarian%')->take(10)->get(['nombre']);
foreach ($ej as $e) {
    echo "  - " . $e->nombre . "\n";
}

echo "\n=== Buscar 'hip thrust' ===\n";
$ej = Ejercicio::where('source', 'visualgym')->where('nombre', 'like', '%hip thrust%')->take(10)->get(['nombre']);
foreach ($ej as $e) {
    echo "  - " . $e->nombre . "\n";
}
