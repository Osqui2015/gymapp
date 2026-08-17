<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

foreach (['francés', 'frances', 'cuádriceps', 'cuadriceps', 'extens'] as $term) {
    echo "Buscando '$term':" . PHP_EOL;
    foreach (App\Models\Ejercicio::where('nombre', 'like', "%$term%")->get(['id', 'nombre']) as $e) {
        echo "  [" . $e->id . "] " . $e->nombre . PHP_EOL;
    }
    echo PHP_EOL;
}
