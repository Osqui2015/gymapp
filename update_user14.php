<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// UPDATE one-shot para destrabar al user 14 (su ciclo ya estaba cerrado
// antes de que existiera la columna ciclo_inicio). Aplica a una sola fila.
$affected = DB::table('user_rutinas')
    ->where('user_id', 14)
    ->update(['ciclo_inicio' => now()->toDateString()]);

echo "Filas actualizadas: {$affected}" . PHP_EOL;

echo "=== DESPUÉS del UPDATE ===" . PHP_EOL;
$fila = DB::table('user_rutinas')->where('user_id', 14)->first();
print_r($fila);
