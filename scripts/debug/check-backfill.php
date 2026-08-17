<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Backfill de rutinas.ejercicio_id ===" . PHP_EOL;
$totalRutinas = App\Models\Rutina::count();
$rutinasConFK = App\Models\Rutina::whereNotNull('ejercicio_id')->count();
echo "Total rutinas: $totalRutinas" . PHP_EOL;
echo "Con ejercicio_id: $rutinasConFK" . PHP_EOL;
echo "Sin ejercicio_id: " . ($totalRutinas - $rutinasConFK) . PHP_EOL;

echo PHP_EOL . "=== Backfill de historials.ejercicio_id ===" . PHP_EOL;
$totalHist = App\Models\Historial::count();
$histConFK = App\Models\Historial::whereNotNull('ejercicio_id')->count();
echo "Total historiales: $totalHist" . PHP_EOL;
echo "Con ejercicio_id: $histConFK" . PHP_EOL;
echo "Sin ejercicio_id: " . ($totalHist - $histConFK) . PHP_EOL;

echo PHP_EOL . "=== Muestra de historiales con su FK ===" . PHP_EOL;
foreach (App\Models\Historial::take(5)->get() as $h) {
    $ejNombre = $h->ejercicioRef?->nombre ?? '(no FK)';
    echo sprintf('[%d] nombre=%s FK_id=%s FK_nombre=%s peso=%s',
        $h->id, $h->ejercicio_nombre, $h->ejercicio_id ?? 'null', $ejNombre, $h->peso
    ) . PHP_EOL;
}

echo PHP_EOL . "=== user_rutinas check ===" . PHP_EOL;
echo "Total user_rutinas: " . App\Models\UserRutina::count() . PHP_EOL;
echo "user_rutinas con rutina_id: " . App\Models\UserRutina::whereNotNull('rutina_id')->count() . PHP_EOL;
echo "user_rutinas con rutina_id + relacion cargada funciona: " . PHP_EOL;
foreach (App\Models\UserRutina::take(3)->get() as $ur) {
    echo sprintf('  [%d] user=%d rutina_id=%s nivel_via_accessor=%s',
        $ur->id, $ur->user_id, $ur->rutina_id ?? 'null', $ur->nivel ?? 'null'
    ) . PHP_EOL;
}
