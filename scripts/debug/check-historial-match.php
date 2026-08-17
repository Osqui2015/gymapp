<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Nombres en historiales (distinct):" . PHP_EOL;
foreach (App\Models\Historial::select('ejercicio_nombre')->distinct()->pluck('ejercicio_nombre') as $h) {
    $exists = App\Models\Ejercicio::where('nombre', $h)->exists();
    echo sprintf("  %-40s [%s]", $h, $exists ? 'OK' : 'NO MATCH') . PHP_EOL;
}

echo PHP_EOL . "Similares en biblioteca (Sentadilla):" . PHP_EOL;
foreach (App\Models\Ejercicio::where('nombre', 'like', '%Sentadilla%')->take(5)->get(['id', 'nombre']) as $e) {
    echo "  [" . $e->id . "] " . $e->nombre . PHP_EOL;
}

echo PHP_EOL . "Similares en biblioteca (Prensa):" . PHP_EOL;
foreach (App\Models\Ejercicio::where('nombre', 'like', '%Prensa%')->take(5)->get(['id', 'nombre']) as $e) {
    echo "  [" . $e->id . "] " . $e->nombre . PHP_EOL;
}
