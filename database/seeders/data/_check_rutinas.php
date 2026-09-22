<?php
require __DIR__ . '/../../../vendor/autoload.php';
$app = require __DIR__ . '/../../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Rutinas por nivel (created_by=NULL = oficiales) ===\n";
$rows = DB::table('rutinas')
    ->select('nivel', 'created_by', DB::raw('count(*) as total'), DB::raw('count(distinct modalidad) as modalidades'))
    ->groupBy('nivel', 'created_by')
    ->get();
foreach ($rows as $r) {
    echo sprintf("%-15s | created_by=%-6s | total=%-4d | modalidades=%d\n",
        $r->nivel, $r->created_by ?? 'NULL', $r->total, $r->modalidades);
}

echo "\n=== Oscar (buscar id) ===\n";
$oscar = DB::table('users')->where('nick', 'admin')->orWhere('email', 'like', '%oscar%')->first();
echo $oscar ? "Oscar: id={$oscar->id} nick={$oscar->nick} role={$oscar->role}\n" : "no encontrado\n";

echo "\n=== Primeras 5 rutinas (cualquier nivel) ===\n";
$first = DB::table('rutinas')->limit(5)->get(['id', 'nivel', 'modalidad', 'dia', 'ejercicio_nombre', 'created_by']);
foreach ($first as $r) {
    echo "{$r->id} | {$r->nivel} | {$r->modalidad} | {$r->dia} | {$r->ejercicio_nombre} | cb=" . ($r->created_by ?? 'NULL') . "\n";
}
