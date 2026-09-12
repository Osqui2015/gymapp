<?php

use App\Models\Historial;
use App\Models\Rutina;
use App\Models\UserRutina;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

echo '=== Backfill de rutinas.ejercicio_id ==='.PHP_EOL;
$totalRutinas = Rutina::count();
$rutinasConFK = Rutina::whereNotNull('ejercicio_id')->count();
echo "Total rutinas: $totalRutinas".PHP_EOL;
echo "Con ejercicio_id: $rutinasConFK".PHP_EOL;
echo 'Sin ejercicio_id: '.($totalRutinas - $rutinasConFK).PHP_EOL;

echo PHP_EOL.'=== Backfill de historials.ejercicio_id ==='.PHP_EOL;
$totalHist = Historial::count();
$histConFK = Historial::whereNotNull('ejercicio_id')->count();
echo "Total historiales: $totalHist".PHP_EOL;
echo "Con ejercicio_id: $histConFK".PHP_EOL;
echo 'Sin ejercicio_id: '.($totalHist - $histConFK).PHP_EOL;

echo PHP_EOL.'=== user_rutinas check ==='.PHP_EOL;
echo 'Total user_rutinas: '.UserRutina::count().PHP_EOL;
echo 'user_rutinas con rutina_id: '.UserRutina::whereNotNull('rutina_id')->count().PHP_EOL;
foreach (UserRutina::with('rutina')->take(3)->get() as $ur) {
    echo sprintf('  [%d] user=%d rutina_id=%s nivel=%s',
        $ur->id, $ur->user_id, $ur->rutina_id ?? 'null', $ur->nivel ?? 'null'
    ).PHP_EOL;
}
