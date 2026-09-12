<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\UserRutina;
use Illuminate\Support\Facades\DB;

echo "=== user_rutinas estado actual ===" . PHP_EOL;
$rows = DB::table('user_rutinas')
    ->leftJoin('rutinas', 'user_rutinas.rutina_id', '=', 'rutinas.id')
    ->select('user_rutinas.user_id', 'user_rutinas.dia_actual', 'user_rutinas.ciclo_inicio', 'rutinas.nivel', 'rutinas.modalidad')
    ->get();

foreach ($rows as $r) {
    echo "user={$r->user_id} | {$r->nivel} {$r->modalidad} | dia_actual={$r->dia_actual} | ciclo_inicio=" . ($r->ciclo_inicio ?? 'NULL') . PHP_EOL;
}

echo PHP_EOL . "=== Usuarios en cycle-reset (Día 1 + ciclo_inicio null) ===" . PHP_EOL;
$needsFix = DB::table('user_rutinas')
    ->whereNull('ciclo_inicio')
    ->where('dia_actual', 'Día 1')
    ->count();
echo "Cantidad: {$needsFix}" . PHP_EOL;
