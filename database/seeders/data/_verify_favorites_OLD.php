<?php

require __DIR__.'/../../../vendor/autoload.php';
$app = require_once __DIR__.'/../../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Ejercicio;
use App\Models\EjercicioFavorito;
use App\Models\User;

$admin = User::where('nick', 'admin')->first();
$e0025 = Ejercicio::where('external_id', '0025')->first();

echo "Admin id: {$admin->id}\n";
echo "Ejercicio 0025 id: {$e0025->id} ({$e0025->nombre})\n";

// Limpiar estado previo
EjercicioFavorito::where('user_id', $admin->id)
    ->where('ejercicio_id', $e0025->id)
    ->delete();

// Estado 1: sin favorito
echo "Estado inicial: is_favorite = " .
    (EjercicioFavorito::where('user_id', $admin->id)->where('ejercicio_id', $e0025->id)->exists() ? 'true' : 'false') . "\n";

// Llamar toggleFavorite directamente (la lógica del controller)
$request = new \Illuminate\Http\Request();
$request->setUserResolver(fn () => $admin);
$ctrl = new \App\Http\Controllers\EjercicioController();

// Toggle 1: crea
$resp1 = $ctrl->toggleFavorite($request, $e0025->id);
echo "Toggle 1 body: " . $resp1->getContent() . "\n";
echo "  DB: " . (EjercicioFavorito::where('user_id', $admin->id)->where('ejercicio_id', $e0025->id)->exists() ? 'favorito creado' : 'NO FAVORITO') . "\n";

// Verificar que el listado lo refleja
$q = Ejercicio::query()->fromVisualGym()->where('external_id', '0025');
$q->addSelect([
    'ejercicios.*',
    'is_favorite' => \DB::table('ejercicio_favoritos')
        ->selectRaw('1')
        ->whereColumn('ejercicio_id', 'ejercicios.id')
        ->where('user_id', $admin->id)
        ->limit(1),
]);
$row = $q->first();
echo "  Listado refleja is_favorite (raw): " . var_export($row->is_favorite, true) . "\n";

// Toggle 2: borra
$resp2 = $ctrl->toggleFavorite($request, $e0025->id);
echo "Toggle 2 body: " . $resp2->getContent() . "\n";
echo "  DB: " . (EjercicioFavorito::where('user_id', $admin->id)->where('ejercicio_id', $e0025->id)->exists() ? 'todavía existe (BUG)' : 'favorito borrado OK') . "\n";

echo "\n=== Verificar endpoints traducidos ===\n";
$req2 = \Illuminate\Http\Request::create('/api/visualgym/facets', 'GET');
$req2->setUserResolver(fn () => $admin);
$resp = $ctrl->facetsVisualGym();
$facets = json_decode($resp->getContent(), true);
echo "Facets.body_parts[0]: value={$facets['body_parts'][0]['value']}, label_es={$facets['body_parts'][0]['label_es']}\n";
echo "Facets.equipamientos[3]: value={$facets['equipamientos'][3]['value']}, label_es={$facets['equipamientos'][3]['label_es']}\n";
