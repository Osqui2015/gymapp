<?php
require __DIR__ . '/../../../vendor/autoload.php';
$app = require __DIR__ . '/../../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Cargar NAME_ALIASES del controller
$src = file_get_contents(__DIR__ . '/../../../app/Http/Controllers/EjercicioController.php');
preg_match('/private const NAME_ALIASES = \[(.*?)\];/s', $src, $m);
$body = $m[1];

// Parse simple de array
$body = preg_replace('/\/\/.*$/m', '', $body);
preg_match_all("/'((?:[^'\\\\]|\\\\.)*)'\s*=>\s*'((?:[^'\\\\]|\\\\.)*)'/", $body, $matches, PREG_SET_ORDER);

$aliases = [];
foreach ($matches as $mm) {
    $aliases[stripslashes($mm[1])] = stripslashes($mm[2]);
}

// Legacy names
$legacy = DB::table('ejercicios')->whereNull('source')->orderBy('nombre')->get(['id', 'nombre']);
echo "Total legacy: " . $legacy->count() . PHP_EOL;
echo "Aliases: " . count($aliases) . PHP_EOL;

$sinMatch = [];
foreach ($legacy as $r) {
    $norm = strtolower(trim(preg_replace('/\s+/', ' ', $r->nombre)));
    $found = false;
    foreach (array_keys($aliases) as $k) {
        $kNorm = strtolower(trim(preg_replace('/\s+/', ' ', $k)));
        if ($kNorm === $norm) {
            $found = true;
            break;
        }
    }
    if (!$found) {
        $sinMatch[] = ['id' => $r->id, 'nombre' => $r->nombre, 'norm' => $norm];
    }
}

echo "Sin match: " . count($sinMatch) . PHP_EOL;
echo PHP_EOL;
foreach ($sinMatch as $sm) {
    echo $sm['id'] . "\t" . $sm['nombre'] . "\n";
}
