<?php
require __DIR__ . '/../../../vendor/autoload.php';
$app = require __DIR__ . '/../../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

function normalizeNoAccents($s) {
    $s = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $s);
    $s = preg_replace('/[^a-z0-9\s\/()]/', ' ', $s);
    $s = preg_replace('/\s+/', ' ', $s);
    return trim($s);
}

// Cargar NAME_ALIASES del controller
$src = file_get_contents(__DIR__ . '/../../../app/Http/Controllers/EjercicioController.php');
preg_match('/private const NAME_ALIASES = \[(.*?)\];/s', $src, $m);
$body = $m[1];
$body = preg_replace('/\/\/.*$/m', '', $body);
preg_match_all("/'((?:[^'\\\\]|\\\\.)*)'\s*=>\s*'((?:[^'\\\\]|\\\\.)*)'/", $body, $matches, PREG_SET_ORDER);

$aliases = [];
foreach ($matches as $mm) {
    $aliases[normalizeNoAccents(stripslashes($mm[1]))] = stripslashes($mm[2]);
}

$legacy = DB::table('ejercicios')->whereNull('source')->orderBy('nombre')->get(['id', 'nombre']);

$sinMatch = [];
foreach ($legacy as $r) {
    $norm = normalizeNoAccents($r->nombre);
    if (!isset($aliases[$norm]) || $aliases[$norm] === '') {
        $sinMatch[] = ['id' => $r->id, 'nombre' => $r->nombre, 'norm' => $norm];
    }
}

echo "Total legacy: " . $legacy->count() . PHP_EOL;
echo "Aliases únicos normalizados: " . count($aliases) . PHP_EOL;
echo "Sin match real: " . count($sinMatch) . PHP_EOL;
echo PHP_EOL;
foreach ($sinMatch as $sm) {
    echo $sm['id'] . "\t" . $sm['nombre'] . "\n";
}
