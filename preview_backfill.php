<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Candidatos al backfill ===" . PHP_EOL;
echo "(ciclo_inicio NULL + dia_actual LIKE 'Día 1%')" . PHP_EOL . PHP_EOL;

$candidatos = DB::table('user_rutinas as ur')
    ->leftJoin('rutinas as r', 'ur.rutina_id', '=', 'r.id')
    ->leftJoin('users as u', 'ur.user_id', '=', 'u.id')
    ->whereNull('ur.ciclo_inicio')
    ->where('ur.dia_actual', 'LIKE', 'Día 1%')
    ->select('ur.id', 'ur.user_id', 'u.name', 'u.nick', 'r.nivel', 'r.modalidad', 'ur.dia_actual')
    ->get();

foreach ($candidatos as $c) {
    $rutinaNombre = $c->nivel.' '.$c->modalidad;
    $ultimoDia = DB::table('rutinas')
        ->where('nivel', $c->nivel)
        ->where('modalidad', $c->modalidad)
        ->orderBy('dia', 'desc')
        ->value('dia');

    $tieneUltimo = $ultimoDia
        ? DB::table('historials')
            ->where('user_id', $c->user_id)
            ->where('rutina_nombre', $rutinaNombre)
            ->where('dia', $ultimoDia)
            ->where('completado', true)
            ->exists()
        : false;

    $accion = $tieneUltimo ? '✓ SE ACTUALIZA' : '— se saltea (sin cierre)';
    echo sprintf(
        "  id=%d user_id=%d (%s / @%s) rutina='%s' dia_actual='%s' ultimo='%s' %s\n",
        $c->id, $c->user_id, $c->name, $c->nick, $rutinaNombre, $c->dia_actual, $ultimoDia ?? 'NULL', $accion
    );
}

echo PHP_EOL . "Total candidatos: " . $candidatos->count() . PHP_EOL;
